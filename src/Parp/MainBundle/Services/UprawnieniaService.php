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
        if (PHP_SAPI == 'cli') {
            $this->container->enterScope('request');
            $this->container->set('request', new \Symfony\Component\HttpFoundation\Request(), 'request');
        }
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
            $up = explode(",", $person->getInitialRights());
            foreach($up as $kkod){
                $noweUprawnienia = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneBy(array('kod' => $kkod));
                foreach ($noweUprawnienia->getUprawnienia() as $uprawnienie) {
                    $nowe[] = $uprawnienie->getId();
                }
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
            $usergrupa = $this->doctrine->getRepository('ParpMainBundle:Usergrupa')->findBy(array('samaccountname' => $person->getSamaccountname()));
            $oldgrupy = array();
            $newgrupy = explode(',', $person->getInitialrights());
            foreach($usergrupa as $g){
                $oldgrupy[] = $g->getGrupa();                                
            }
            
            $grupDoUtworzenia = array_diff($newgrupy, $oldgrupy);
            $grupDoSkasowania = array_diff($oldgrupy, $newgrupy);
            
            foreach($grupDoUtworzenia as $ug){
                $usergrupa = new UserGrupa();
                $usergrupa->setGrupa($ug);
                $usergrupa->setSamaccountname($person->getSamaccountname());            
                $this->doctrine->persist($usergrupa);
            }
            foreach($grupDoSkasowania as $ug){
                $usergrupa = $this->doctrine->getRepository('ParpMainBundle:Usergrupa')->findOneBy(array('samaccountname' => $person->getSamaccountname(), 'grupa' => $ug));            
                $this->doctrine->remove($usergrupa);
            }
            
                
                
/*
            if ($usergrupa) {
                $usergrupa->setGrupa($person->getInitialrights());
            } else {
                $usergrupa = new UserGrupa();
                $usergrupa->setGrupa($person->getInitialrights());
                $usergrupa->setSamaccountname($person->getSamaccountname());
            }
            $this->doctrine->persist($usergrupa);
*/

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

    public function wyslij($person, $odebrane = null, $nadane = null, $obiekt = "Uprawnienia", $obiektId = 0, $zadanieDla = 'Jakacki Kamil')
    {
        //$zadanieDla = "Lipiński Marcin";
        $ldap = $this->container->get('ldap_admin_service');
        $dlaKogo = explode(",", $zadanieDla);
        $mails = array();
        foreach($dlaKogo as $user){
            $cn = "CN=".str_replace(" ", "*", trim($user));
            print_r($cn);
            $userAD = $ldap->getUserFromAD(null, $cn);
            print_r($userAD);
            if($userAD && count($userAD) > 0 && $userAD[0]['email'] != "")
                $mails[] = $userAD[0]['email'];
        }
        
        
        print_r ($mails);
        
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
         $o1 = (count($nadane) > 0 ? " nadanie " : "").(count($nadane) > 0 && count($odebrane) > 0 ? " i " : "").(count($odebrane) > 0 ? " odebranie " : "");
         $opis = $obiekt.($obiektId != 0 ? " o id : ".$obiektId : "")." dla użytkownika ".$person['cn'];
         $zadanie = new \Parp\MainBundle\Entity\Zadanie();
         $zadanie->setNazwa("Nowe zadanie o ".$o1." dot. ".$opis);
         $zadanie->setOsoby($zadanieDla);
         $zadanie->setDataDodania(new \Datetime());
         $zadanie->setObiekt($obiekt);
         $zadanie->setObiektId($obiektId);
         $zadanie->setStatus('utworzone');
         $this->doctrine->persist($zadanie);
         $this->doctrine->flush();
        $view = $this->container->get('templating')->render(
                'ParpMainBundle:Default:email.html.twig', array('odebrane' => $odebrane, 'person' => $person, 'nadane' => $nadane, 'zadanie' => $zadanie));

        $mails[] = 'kamil_jakacki@parp.gov.pl';
        //$mails[] = 'kamil@zapytania.com';

        $message = \Swift_Message::newInstance()
                ->setSubject('Zmiana uprawnień')
                //->setFrom('intranet@parp.gov.pl')
                ->setFrom("kamikacy@gmail.com")
                ->setTo($mails)
                ->setBody($view)
                ->setContentType("text/html");

        //var_dump($view);
        $this->container->get('mailer')->send($message);
        
        
        $zadanie->setOpis($view);
         $this->doctrine->persist($zadanie);
         $this->doctrine->flush();
        
        //die();
    }

}
