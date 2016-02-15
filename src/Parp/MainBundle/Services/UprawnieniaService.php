<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace Parp\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Entity\UserGrupa;

class UprawnieniaService
{

    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
    }

    public function ustawPoczatkowe($person)
    {

        $poczatkowe = $person->getInitialrights();
        $grupa = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneByKod($poczatkowe);
        $uprawnienia = $grupa->getUprawnienia();

        // znajdz biuro i sekcje
        $departament = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());

        //echo $person->getSamaccountname();
        $nadane = array();

        foreach ($uprawnienia as $uprawnienie) {

            if ($uprawnienie->getCzyEdycja()) {

                if ($uprawnienie->getCzySekcja()) {

                    if (mb_strtoupper($section->getShortname() != 'ND')) {

                        // tylko w tym wypadku podmieniamy sekcję
                        // jezeli nie to nie wstawiamy nic

                        $opis = str_replace('[sekcja]', $section->getShortname(), $uprawnienie->getOpis());
                        $opis = str_replace('[D/B]', $departament->getShortname(), $opis);

                        $userUprawnienia = new UserUprawnienia();
                        $userUprawnienia->setOpis($opis);
                        $userUprawnienia->setDataNadania(new \DateTime());
                        $userUprawnienia->setCzyAktywne(true);
                        $userUprawnienia->setSamaccountname($person->getSamaccountname());
                        $userUprawnienia->setUprawnienieId($uprawnienie->getId());

                        $nadane[] = $opis;
                    }
                } else {

                    $userUprawnienia = new UserUprawnienia();
                    $opis = str_replace('[D/B]', $departament->getShortname(), $uprawnienie->getOpis());
                    $userUprawnienia->setOpis($opis);
                    $userUprawnienia->setDataNadania(new \DateTime());
                    $userUprawnienia->setCzyAktywne(true);
                    $userUprawnienia->setSamaccountname($person->getSamaccountname());
                    $userUprawnienia->setUprawnienieId($uprawnienie->getId());

                    $nadane[] = $opis;
                }
            } else {

                $userUprawnienia = new UserUprawnienia();
                $userUprawnienia->setOpis($uprawnienie->getOpis());
                $userUprawnienia->setDataNadania(new \DateTime());
                $userUprawnienia->setCzyAktywne(true);
                $userUprawnienia->setSamaccountname($person->getSamaccountname());
                $userUprawnienia->setUprawnienieId($uprawnienie->getId());

                $nadane[] = $opis;
            }


            $this->doctrine->persist($userUprawnienia);

            $this->wyslij($person, null, $nadane);
        }

        $ug = new UserGrupa();
        $ug->setSamaccountname($person->getSamaccountname());
        $ug->setGrupa($poczatkowe);
        $this->doctrine->persist($ug);

        $this->doctrine->flush();

        $this->wyslij($person);
    }

    public function zmianaUprawnien($person)
    {
        $czyZmianaSekcji = false;
        $czyZmianaDepartamentu = false;
        $czyZmianaGrupyUprawnien = false;

        //pobierz aktualne  uprawnienia
        $uprawnienia = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $person->getSamaccountname()));

        // sprawdz czy nastapiła zmina sekcji i biura
        if ($person->getInitialRights()) {
            $czyZmianaGrupyUprawnien = true;
        }
        if ($person->getDepartment()) {
            $czyZmianaDepartamentu = true;
        }
        if ($person->getInfo()) {
            //echo $person->getInfo();
            $czyZmianaSekcji = true;
        }

        if ($czyZmianaGrupyUprawnien === true) {

            //pobierz nowe uprawnienia
            $nowe = array();
            $noweUprawnienia = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneBy(array('kod' => $person->getInitialRights()));
            foreach ($noweUprawnienia->getUprawnienia() as $uprawnienie) {
                $nowe[] = $uprawnienie->getId();
            }

            $istniejace = array();
            //pobierz istniejace
            $istniejąceUprawnienia = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $person->getSamaccountname(), 'czyAktywne' => TRUE));
            foreach ($istniejąceUprawnienia as $uprawnienie) {
                $istniejace[] = $uprawnienie->getUprawnienieId();
            }

            $doDodania = array();
            $doUsuniecia = array();

            // utworz tablice zmian
            foreach ($nowe as $value) {
                if (!in_array($value, $istniejace)) {
                    $doDodania[] = $value;
                }
            }

            foreach ($istniejace as $value) {
                if (!in_array($value, $nowe)) {
                    $doUsuniecia[] = $value;
                }
            }

            //obsłuz usuniecie
            foreach ($doUsuniecia as $value) {
                // znajdz uprawnienie uzytkownika
                $upr = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $person->getSamaccountname(), 'czyAktywne' => TRUE, 'uprawnienie_id' => $value));
                $upr->setCzyAktywne(false);
                $upr->setDataOdebrania(new \DateTime());
                //echo($upr ->getOpis()) . "\n";
                // todo
                // dane do maila

                $this->doctrine->persist($upr);
            }

            foreach ($doDodania as $value) {

                // pobierz ze slownika
                $upr = $this->doctrine->getRepository('ParpMainBundle:Uprawnienia')->findOneById($value);

                if ($upr->getCzyEdycja()) {

                    $ldap = $this->container->get('ldap_admin_service');
                    $userAD = $ldap->getUserFromAD($person->getSamaccountname());

                    $shortname = $userAD[0]['division'];
                    $description = $userAD[0]['description'];

                    if ($upr->getCzySekcja()) {
                        if (mb_strtoupper($shortname != 'ND')) {

                            // tylko w tym wypadku podmieniamy sekcję
                            // jezeli nie to nie wstawiamy nic
                            $opis = str_replace('[sekcja]', $shortname, $upr->getOpis());
                            $opis = str_replace('[D/B]', $description, $opis);

                            $nowe = new UserUprawnienia();
                            $nowe->setOpis($opis);
                            $nowe->setDataNadania(new \DateTime());
                            $nowe->setCzyAktywne(true);
                            $nowe->setSamaccountname($person->getSamaccountname());
                            $nowe->setUprawnienieId($upr->getId());
                            $this->doctrine->persist($nowe);
                        }
                    } else {
                        $nowe = new UserUprawnienia();
                        $opis = str_replace('[D/B]', $description, $upr->getOpis());
                        $nowe->setOpis($opis);
                        $nowe->setDataNadania(new \DateTime());
                        $nowe->setCzyAktywne(true);
                        $nowe->setSamaccountname($person->getSamaccountname());
                        $nowe->setUprawnienieId($upr->getId());
                        $this->doctrine->persist($nowe);
                    }
                } else {
                    $nowe = new UserUprawnienia();
                    $nowe->setCzyAktywne(true);
                    $nowe->setDataNadania(new \DateTime());
                    $nowe->setSamaccountname($person->getSamaccountname());
                    $nowe->setOpis($upr->getOpis());
                    $nowe->setUprawnienieId($upr->getId());

                    $this->doctrine->persist($nowe);
                }
            }

            // zmien grupę uprawneń
            $usergrupa = $this->doctrine->getRepository('ParpMainBundle:Usergrupa')->findOneBy(array('samaccountname' => $person->getSamaccountname()));
            if ($usergrupa) {
                $usergrupa->setGrupa($person->getInitialrights());
            } else {
                $usergrupa = new UserGrupa();
                $usergrupa->setGrupa($person->getInitialrights());
                $usergrupa->setSamaccountname($person->getSamaccountname());
            }
            $this->doctrine->persist($usergrupa);

            $this->doctrine->flush();
        }

        if ($czyZmianaDepartamentu === true) {

            $nadane = array();
            $odebrane = array();

            $uprawnieniauser = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findDepartament($person->getSamaccountname());

            //ustaw stare na nieaktualne
            foreach ($uprawnieniauser as $uprawnienieuser) {
                $uprawnienieuser->setCzyAktywne(false);
                $uprawnienieuser->setdataOdebrania(new \DateTime());
                $this->doctrine->persist($uprawnienieuser);

                $odebrane[] = $uprawnienieuser->getOpis();
            }
            // znajdz biuro i sekcje
            $departament = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());

            if ($person->getInfo()) {
                $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
                $shortname = $section->getShortname();
            }// jezeli nie zmieniona sekcja pobierz z ldap-a
            else {
                $ldap = $this->container->get('ldap_admin_service');
                $userAD = $ldap->getUserFromAD($person->getSamaccountname());
                $shortname = $userAD[0]['division'];
            }
            //znajdz te do edycji
            $grupa = $this->doctrine->getRepository('ParpMainBundle:UserGrupa')->findOneBy(array('samaccountname' => $person->getSamaccountname()));
            if($grupa)
                $edytowanlne = $this->doctrine->getRepository('ParpMainBundle:Uprawnienia')->findEdytowalneDlaGrupy($grupa->getGrupa());
            else
                $edytowanlne = array();

            foreach ($edytowanlne as $edytowalny) {

                if ($edytowalny->getCzySekcja()) {

                    if (mb_strtoupper($shortname != 'ND')) {

                        // tylko w tym wypadku podmieniamy sekcję
                        // jezeli nie to nie wstawiamy nic

                        $opis = str_replace('[sekcja]', $shortname, $edytowalny->getOpis());

                        $opis = str_replace('[D/B]', $departament->getShortname(), $opis);

                        $userUprawnienia = new UserUprawnienia();
                        $userUprawnienia->setOpis($opis);
                        $userUprawnienia->setDataNadania(new \DateTime());
                        $userUprawnienia->setCzyAktywne(true);
                        $userUprawnienia->setSamaccountname($person->getSamaccountname());
                        $userUprawnienia->setUprawnienieId($edytowalny->getId());

                        $nadane[] = $opis;
                    }
                } else {

                    $userUprawnienia = new UserUprawnienia();
                    $opis = str_replace('[D/B]', $departament->getShortname(), $edytowalny->getOpis());
                    $userUprawnienia->setOpis($opis);
                    $userUprawnienia->setDataNadania(new \DateTime());
                    $userUprawnienia->setCzyAktywne(true);
                    $userUprawnienia->setSamaccountname($person->getSamaccountname());
                    $userUprawnienia->setUprawnienieId($edytowalny->getId());

                    $nadane[] = $opis;
                }

                $this->doctrine->persist($userUprawnienia);
            }

            $this->wyslij($person, $odebrane, $nadane);
        } elseif ($czyZmianaSekcji === true) {
            $nadane = array();
            $odebrane = array();
            // znajdz stare uprawnienie       
            $uprawnienieuser = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findSekcja($person->getSamaccountname());
            //ustaw stare na niekatualne i wtsaw date
            if($uprawnienieuser){
                $uprawnienieuser->setCzyAktywne(false);
                $uprawnienieuser->setdataOdebrania(new \DateTime());
    
                $odebrane[] = $uprawnienieuser->getOpis();
    
                $this->doctrine->persist($uprawnienieuser);
            }

            $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
            if (mb_strtoupper($section->getShortname() !== 'ND') && $uprawnienieuser) {
                $id = $uprawnienieuser->getUprawnienieId();
                //$this->doctrine->persist($uprawnienie);
                //utworz nowe z nawą konta
                $nowe = new UserUprawnienia();
                $nowe->setCzyAktywne(true);
                $nowe->setDataNadania(new \DateTime());
                $nowe->setSamaccountname($person->getSamaccountname());
                $nowe->setUprawnienieId($id); // ustaw klucz
                // pobierz i podmieñ opis
                //echo $id;
                $uprawnienie = $this->doctrine->getRepository('ParpMainBundle:Uprawnienia')->findOneById($id);

                $opis = $uprawnienie->getOpis();

                //Przydaøoby sie info o biurze 
                $ldap = $this->container->get('ldap_admin_service');
                $userAD = $ldap->getUserFromAD($person->getSamaccountname());
                $opis = str_replace('[sekcja]', $userAD[0]['division'], $opis);
                $opis = str_replace('[D/B]', $userAD[0]['description'], $opis);

                $nowe->setOpis($opis);
                $this->doctrine->persist($nowe);
                $nadane[] = $opis;
            }

            $this->doctrine->flush();
            $this->wyslij($person, $odebrane, $nadane);
        }
    }

    public function wyslij($person, $odebrane = null, $nadane = null)
    {
        //$view = $this->container->get('templating')->render(
        //'BatchingBundle:Default:email.html.twig', array('content' => $content)
        //);
        /*
          $uprawnienia = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')
          ->findBy(array('samaccountname' => $person->getSamaccountname(), 'czyAktywne' => TRUE), array('uprawnienie_id' => 'asc'));

          $dane = array();
          foreach ($uprawnienia as $uprawnienie) {
          //echo $uprawnienie->getOpis();
          $dane[] = $uprawnienie->getOpis();
          }
         */

        $view = $this->container->get('templating')->render(
                'ParpMainBundle:Default:email.html.twig', array('odebrane' => $odebrane, 'person' => $person, 'nadane' => $nadane));

        $message = \Swift_Message::newInstance()
                ->setSubject('Zmiana uprawnień')
                ->setFrom('intranet@parp.gov.pl')
                ->setTo('kamil_jakackik@parp.gov.pl')
                ->setBody($view)
                ->setContentType("text/html");

        //var_dump($view);
        $this->container->get('mailer')->send($message);
    }

}
