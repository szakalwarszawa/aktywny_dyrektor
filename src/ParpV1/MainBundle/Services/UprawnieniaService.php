<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\Zadanie;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\UserGrupa;
use ParpV1\MainBundle\Services\RedmineConnectService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UprawnieniaService
 * @package ParpV1\MainBundle\Services
 */
class UprawnieniaService
{
    /** @var EntityManager $doctrine */
    protected $doctrine;
    /** @var Container $container */
    protected $container;

    /**
     * UprawnieniaService constructor.
     * @param EntityManager $OrmEntity
     * @param Container $container
     */
    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->setDoctrine($OrmEntity);
        $this->setContainer($container);

        if (PHP_SAPI == 'cli') {
            $this->container->enterScope('request');
            $this->container->set('request', new Request(), 'request');
        }
    }

    /**
     * @param Entry $person
     */
    public function ustawPoczatkowe(Entry $person)
    {
        $uprawnienia = array();
        //pobierz nowe uprawnienia
        $grupy = array();
        $up = explode(",", $person->getInitialrights());
        foreach ($up as $kkod) {
            if ($kkod != "") {
                $noweUprawnienia = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneBy(['kod' => $kkod]);
                $grupy[] = $noweUprawnienia;
                if (null !== $noweUprawnienia) {
                    foreach ($noweUprawnienia->getUprawnienia() as $uprawnienie) {
                        $uprawnienia[$uprawnienie->getId()] = $uprawnienie;
                    }
                }
            }
        }


        /*
        $poczatkowe = $person->getInitialrights();
        $grupa = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneByKod($poczatkowe);
        $uprawnienia = $grupa->getUprawnienia();
        */

        // znajdz biuro i sekcje
        $departament = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneBy([
            'name' => $person->getDepartment()
        ]);
        $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneBy([
            'name' => $person->getInfo(),
        ]);

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
                        $userUprawnienia
                            ->setOpis($opis)
                            ->setDataNadania(new \DateTime())
                            ->setCzyAktywne(true)
                            ->setSamaccountname($person->getSamaccountname())
                            ->setUprawnienieId($uprawnienie->getId())
                        ;

                        $nadane[] = $opis;
                    }
                } else {
                    $userUprawnienia = new UserUprawnienia();
                    $opis = str_replace('[D/B]', $departament->getShortname(), $uprawnienie->getOpis());
                    $userUprawnienia
                        ->setOpis($opis)
                        ->setDataNadania(new \DateTime())
                        ->setCzyAktywne(true)
                        ->setSamaccountname($person->getSamaccountname())
                        ->setUprawnienieId($uprawnienie->getId())
                    ;

                    $nadane[] = $opis;
                }
            } else {
                $opis = str_replace('[sekcja]', $section->getShortname(), $uprawnienie->getOpis());
                $opis = str_replace('[D/B]', $departament->getShortname(), $opis);

                $userUprawnienia = new UserUprawnienia();
                $userUprawnienia
                    ->setOpis($uprawnienie->getOpis())
                    ->setDataNadania(new \DateTime())
                    ->setCzyAktywne(true)
                    ->setSamaccountname($person->getSamaccountname())
                    ->setUprawnienieId($uprawnienie->getId())
                ;

                $nadane[] = $opis;
            }


            $this->doctrine->persist($userUprawnienia);

            $this->wyslij($person, null, $nadane);
        }
        foreach ($grupy as $g) {
            $ug = new UserGrupa();
            $ug->setSamaccountname($person->getSamaccountname());
            $ug->setGrupa($g);
            $this->doctrine->persist($ug);
        }
        $this->doctrine->flush();

        $this->wyslij($person);
    }

    /**
     * @param $person
     * @param null $odebrane
     * @param null $nadane
     * @param string $obiekt
     * @param int $obiektId
     * @param string $zadanieDla
     * @param null $wniosek
     */
    public function wyslij($person, $odebrane = null, $nadane = null, $obiekt = "Uprawnienia", $obiektId = 0, $zadanieDla = 'Jakacki Kamil', $wniosek = null)
    {
        //$zadanieDla = "Lipiński Marcin";
        $ldap = $this->container->get('ldap_service');
        $dlaKogo = explode(",", $zadanieDla);
        $mails = array();
        foreach ($dlaKogo as $user) {
            $cn = "CN=" . str_replace(" ", "*", trim($user));
            //print_r($cn);
            $userAD = $ldap->getUserFromAD(null, $cn);
            //print_r($userAD);
            if ($userAD && count($userAD) > 0 && $userAD[0]['email'] != "") {
                $mails[] = 'kamil_jakacki@parp.gov.pl'; //$userAD[0]['email'];
            }
        }


        //print_r ($mails);

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
        $o1 = (count($nadane) > 0 ? " nadanie " : "") . (count($nadane) > 0 && count($odebrane) > 0 ? " i " : "") . (count($odebrane) > 0 ? " odebranie " : "");
        $opis = $obiekt . ($obiektId != 0 ? " o id : " . $obiektId : "") . " dla użytkownika " . (is_array($person) ? $person['cn'] : $person->getCn());
        $zadanie = new Zadanie();
        $zadanie->setNazwa("Nowe zadanie o " . $o1 . " dot. " . $opis);
        $zadanie->setOsoby($zadanieDla);
        $zadanie->setDataDodania(new \Datetime());
        $zadanie->setObiekt($obiekt);
        $zadanie->setObiektId($obiektId);
        $zadanie->setStatus('utworzone');
        $this->doctrine->persist($zadanie);
        $this->doctrine->flush();
        $view = $this->container->get('templating')->render(
            'ParpMainBundle:Default:email.html.twig',
            array('odebrane' => $odebrane, 'person' => $person, 'nadane' => $nadane, 'zadanie' => $zadanie)
        );

        $mails[] = 'kamil_jakacki@parp.gov.pl';
        //$mails[] = 'kamil@zapytania.com';

        $message = \Swift_Message::newInstance()
            ->setSubject('Zmiana uprawnień')
            ->setFrom('intranet@parp.gov.pl')
            //->setFrom("kamikacy@gmail.com")
            ->setTo($mails)
            ->setBody($view)
            ->setContentType("text/html");

        //var_dump($view);

        if ('kjakacki' == 'WYLACZAM') {
            $this->container->get('mailer')->send($message);
        }


        $zadanie->setOpis($view);
        $this->doctrine->persist($zadanie);
        $this->doctrine->flush();

        //die();
    }

    /**
     * @param Entry $person
     */
    public function zmianaUprawnien(Entry $person)
    {
        $czyZmianaSekcji = false;
        $czyZmianaDepartamentu = false;
        $czyZmianaGrupyUprawnien = false;

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
            foreach ($up as $kkod) {
                $noweUprawnienia = $this->doctrine->getRepository('ParpMainBundle:GrupyUprawnien')->findOneBy(array('kod' => $kkod));
                if (null !== $noweUprawnienia) {
                    foreach ($noweUprawnienia->getUprawnienia() as $uprawnienie) {
                        $nowe[] = $uprawnienie->getId();
                    }
                }
            }

            $istniejace = array();
            //pobierz istniejace
            $istniejaceUprawnienia = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $person->getSamaccountname(), 'czyAktywne' => true));
            foreach ($istniejaceUprawnienia as $uprawnienie) {
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
                $upr = $this->doctrine->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $person->getSamaccountname(), 'czyAktywne' => true, 'uprawnienie_id' => $value));
                $upr->setCzyAktywne(false);
                $upr->setDataOdebrania(new \DateTime());
                //echo($upr ->getOpis()) . "\n";
                // todo
                // dane do maila

                $this->doctrine->persist($upr);
            }

            foreach ($doDodania as $value) {
                // pobierz ze slownika
                $upr = $this->doctrine->getRepository('ParpMainBundle:Uprawnienia')->findOneBy(['id' => $value]);

                if ($upr->getCzyEdycja()) {
                    $ldap = $this->container->get('ldap_service');
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
            $usergrupa = $this->doctrine->getRepository('ParpMainBundle:UserGrupa')->findBy(array('samaccountname' => $person->getSamaccountname()));
            $oldgrupy = array();
            $newgrupy = explode(',', $person->getInitialrights());
            foreach ($usergrupa as $g) {
                $oldgrupy[] = $g->getGrupa();
            }

            $grupDoUtworzenia = array_diff($newgrupy, $oldgrupy);
            $grupDoSkasowania = array_diff($oldgrupy, $newgrupy);

            foreach ($grupDoUtworzenia as $ug) {
                $usergrupa = new UserGrupa();
                $usergrupa->setGrupa($ug);
                $usergrupa->setSamaccountname($person->getSamaccountname());
                $this->doctrine->persist($usergrupa);
            }
            foreach ($grupDoSkasowania as $ug) {
                $usergrupa = $this->doctrine->getRepository('ParpMainBundle:UserGrupa')->findOneBy(array('samaccountname' => $person->getSamaccountname(), 'grupa' => $ug));
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
            } // jezeli nie zmieniona sekcja pobierz z ldap-a
            else {
                $ldap = $this->container->get('ldap_service');
                $userAD = $ldap->getUserFromAD($person->getSamaccountname());
                $shortname = $userAD[0]['division'];
            }
            //znajdz te do edycji
            $grupa = $this->doctrine->getRepository('ParpMainBundle:UserGrupa')->findOneBy(array('samaccountname' => $person->getSamaccountname()));
            if ($grupa) {
                $edytowanlne = $this->doctrine->getRepository('ParpMainBundle:Uprawnienia')->findEdytowalneDlaGrupy($grupa->getGrupa());
            } else {
                $edytowanlne = array();
            }

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
            if ($uprawnienieuser) {
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
                $ldap = $this->container->get('ldap_service');
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

    /**
     * Zwraca false, jeżeli co najmniej jeden nadany poziom dostępu nie jest prawidłowy.
     *
     * @param string $uprawnieniaString
     * @param int $zasob
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    public function sprawdzPrawidlowoscPoziomuDostepu($uprawnieniaString, $zasob, $pokazRoznice = true)
    {
        $uprawnienia = $this->zwrocUprawnieniaJakoTablica($uprawnieniaString);

        $manager = $this->getDoctrine();

        $zasob = $manager->getRepository('ParpMainBundle:Zasoby')->find($zasob);

        if (null === $zasob) {
            throw new EntityNotFoundException('Nie ma zasobu o takim ID');
        }

        $poziomyDostepu = $this->zwrocUprawnieniaJakoTablica($zasob->getPoziomDostepu());
        $roznice = [];

        $i = 0;
        foreach ($uprawnienia as $item) {
            if (!in_array($item, $poziomyDostepu)) {
                if (true === $pokazRoznice) {
                    $roznice[$i]['jest'] = $item;
                    $roznice[$i]['powinno_byc'] = $poziomyDostepu;
                    $i++;
                } else {
                    return true;
                }
            }
        }

        return count($roznice) > 0 ? $roznice : false;
    }

    /**
     * Zwraca tablicę uprawnień
     *
     * @param $uprawnienia
     * @return array
     */
    public function zwrocUprawnieniaJakoTablica($uprawnienia)
    {
        return explode(';', $uprawnienia);
    }

    /**
     * @return EntityManager
     */
    private function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param EntityManager $doctrine
     * @return UprawnieniaService
     */
    private function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
        return $this;
    }

    /**
     * @return Container
     */
    private function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Container $container
     * @return UprawnieniaService
     */
    private function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }


}