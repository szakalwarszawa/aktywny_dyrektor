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
use ParpV1\MainBundle\Entity\UserZasoby;
use InvalidArgumentException;
use DateTime;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Services\StatusWnioskuService;
use ParpV1\MainBundle\Entity\WniosekStatus;
use Psr\Cache\CacheItemPoolInterface;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\AuthBundle\Security\ParpUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class UprawnieniaService
 * @package ParpV1\MainBundle\Services
 */
class UprawnieniaService
{
    const ZASOBY_CACHE_KEY = 'zasoby_z_grupami';

    /** @var EntityManager $doctrine */
    protected $doctrine;
    /** @var Container $container */
    protected $container;

    /**
     * @var StatusWnioskuService
     */
    private $statusWnioskuService;

    /**
     * @var CacheItemPoolInterface
     */
    private $zasobyCache;

    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * UprawnieniaService constructor.
     * @param EntityManager $OrmEntity
     * @param Container $container
     */
    public function __construct(
        EntityManager $OrmEntity,
        Container $container,
        StatusWnioskuService $statusWnioskuService,
        CacheItemPoolInterface $zasobyCache,
        TokenStorage $tokenStorage
    ) {
        $this->setDoctrine($OrmEntity);
        $this->setContainer($container);
        $this->statusWnioskuService = $statusWnioskuService;
        $this->zasobyCache = $zasobyCache;
        $this->currentUser = $tokenStorage->getToken()->getUser();

        if (PHP_SAPI == 'cli') {
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
            } else {
                // jezeli nie zmieniona sekcja pobierz z ldap-a
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


    public function znajdzGrupeAD($uz, $z)
    {
        if (!$z instanceof Zasoby) {
            $entityManager = $this->getDoctrine();
            $z = $entityManager
                ->getRepository(Zasoby::class)
                ->findOneById($z);
        }

        $grupy = explode(';', $z->getGrupyAD());
        $poziomy = explode(';', $z->getPoziomDostepu());
        $ktoryPoziom = $this->znajdzPoziom($poziomy, $uz->getPoziomDostepu());

        if (!($ktoryPoziom >= 0 && $ktoryPoziom < count($grupy))) {
            //var_dump($grupy, $poziomy, $ktoryPoziom);
        }

        //$uz->getId()." ".$z->getId()." ".
        return  ($ktoryPoziom >= 0 && $ktoryPoziom < count($grupy) ? $grupy[$ktoryPoziom] : '"'.$z->getNazwa().'" ('.$grupy[0].')') ; //$grupy[0];
    }

    public function znajdzPoziom($poziomy, $poziom)
    {
        $i = -1;
        for ($i = 0; $i < count($poziomy); $i++) {
            if (trim($poziomy[$i]) == trim($poziom) || strstr(trim($poziomy[$i]), trim($poziom)) !== false) {
                return $i;
            }
        }
        return $i;
    }

    /**
     * Pobiera zasoby, wnioski danego użytkownika następnie
     * na podstawie datyGranicznej odbiera zasoby przed datą
     * i anuluje administracyjnie / odbiera administracyjnie (Nadaje taki status dla wniosku)
     * lub odbiera pojedyńczy zasób we wniosku (w przypadku wniosku wiele-do-wielu).
     * Metoda domyślnie wykonuje flush, aby tego nie robić $doFlush musi być FALSE.
     *
     * @param string $nazwaUzytkownika
     * @param DateTime $dataGraniczna
     * @param string $komentarzOdebrania
     * @param bool $doFlush
     * @param bool $returnTylkoUserZasoby zwróci tablicę zawierająca tylko
     *      przeprocesowane UserZasoby oraz informację czy zostało stworzone entry do AD.
     *
     * @throws InvalidArgumentException gdy nie podano nazwy użytkownika
     *
     * @return bool|array
     */
    public function odbierzZasobyUzytkownikaOdDaty(
        $nazwaUzytkownika,
        DateTime $dataGraniczna,
        $komentarzOdebrania = null,
        $doFlush = true,
        $returnTylkoUserZasoby = false
    ) {
        if (empty($nazwaUzytkownika)) {
            throw new InvalidArgumentException('Nazwa użytkownika jest pusta.');
        }

        $entityManager = $this->getDoctrine();

        $userZasoby = $entityManager
            ->getRepository(UserZasoby::class)
            ->findAktywneZasobyDlaUzytkownika($nazwaUzytkownika);

        if (empty($userZasoby)) {
            // Brak zasobów do odebrania
            return false;
        }

        $this->przeladujZasobyCache();
        $przeprocesowaneWnioski = [];
        $odebraneUserZasoby = [];
        foreach ($userZasoby as $userZasob) {
            $wnioskiZasobyDoOdebrania = $this->pobierzWnioskiZasobyDoOdebrania($userZasob->getWniosek(), $dataGraniczna);
            if (count($wnioskiZasobyDoOdebrania) && $wnioskiZasobyDoOdebrania['procesuj_zasob']) {
                $wniosekNadanieOdebranieId = $userZasob->getWniosek()->getId();
                $przeprocesowaneWnioski[$wniosekNadanieOdebranieId] = [];
                $przeprocesowaneWnioski[$wniosekNadanieOdebranieId]['zasoby'][] = $userZasob->getId();
                $przeprocesowaneWnioski[$wniosekNadanieOdebranieId]['komentarz'] = $komentarzOdebrania;
                $przeprocesowaneWnioski[$wniosekNadanieOdebranieId]['stworzono_entry'] = false;

                $odebraneUserZasoby[$userZasob->getId()] = [];
                $odebraneUserZasoby[$userZasob->getId()]['zasob'] = $userZasob->getZasobId();
                $odebraneUserZasoby[$userZasob->getId()]['stworzono_entry'] = false;
                $odebraneUserZasoby[$userZasob->getId()]['wniosek_nadanie_odebranie'] = $userZasob
                    ->getWniosek()
                    ->getId()
                ;

                $wnioskiZasobyDoOdebrania['wniosek_nadanie_odebranie'] =  $userZasob->getWniosek();
                $wnioskiZasobyDoOdebrania['user_zasob'] =  $userZasob;

                $odebranyZasobId = $this->odbierzAnulujZasobyAdministracyjnie($wnioskiZasobyDoOdebrania, $komentarzOdebrania);
                if ($this->czyTworzycEntry($userZasob)
                    && $wnioskiZasobyDoOdebrania['wniosek_do_odebrania_administracyjnego']) {
                    $zasobyDoUtworzeniaEntry[] = $userZasob;
                    $przeprocesowaneWnioski[$wniosekNadanieOdebranieId]['stworzono_entry'] = true;
                    $odebraneUserZasoby[$userZasob->getId()]['stworzono_entry'] = true;
                }
            }
        }

        if (!empty($zasobyDoUtworzeniaEntry)) {
            $this->stworzEntry($zasobyDoUtworzeniaEntry, $nazwaUzytkownika);
        }
        if ($doFlush) {
            $entityManager->flush();
        }

        if ($returnTylkoUserZasoby) {
            return $odebraneUserZasoby;
        }

        return $przeprocesowaneWnioski;
    }

    /**
     * Utworzenie obiektu entry.
     *
     * @param array $zasobyDoUtworzeniaEntry
     * @param string $nazwaUzytkownika
     *
     * @return void
     */
    private function stworzEntry(array $zasobyDoUtowrzeniaEntry, $nazwaUzytkownika)
    {
        $grupyDoOdebrania = [];

        foreach ($zasobyDoUtowrzeniaEntry as $zasob) {
            $grupyDoOdebrania[] = $this->znajdzGrupeAD($zasob, $zasob->getZasobId());
        }

        $entry = new Entry();
        $entry->setFromWhen(new Datetime());
        $entry->setSamaccountname($nazwaUzytkownika);
        $entry->setMemberOf('-'.implode(',-', $grupyDoOdebrania));
        $entry->setCreatedBy('SYSTEM');
        $entry->setOpis('Odebrano administracyjnie.');
        $this
            ->getDoctrine()
            ->persist($entry);
    }

    /**
     * Sprawdza czy trzeba stworzyć obiekt Entry służący do wypchnięcia zmian do AD.
     *
     * @param UserZasoby $userZasob
     *
     * @return bool
     */
    private function czyTworzycEntry(UserZasoby $userZasob): bool
    {
        $zasobId = $userZasob->getZasobId();

        $zasobyCache = $this->zasobyCache;
        $cacheKey = self::ZASOBY_CACHE_KEY;
        $cacheItem = $zasobyCache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $zasobyZGrupami = $this->pobierzZasobyIdZGrupamiAd();
            $cacheItem->set(serialize($zasobyZGrupami));
            $zasobyCache->save($cacheItem);
        }

        $zasobyZGrupami = unserialize($cacheItem->get());

        if (in_array($zasobId, $zasobyZGrupami)) {
            return true;
        }

        return false;
    }

    /**
     * Odświeża cache zasobow.
     *
     * @return void
     */
    private function przeladujZasobyCache()
    {
        $zasobyCache = $this->zasobyCache;
        $cacheKey = self::ZASOBY_CACHE_KEY;
        $cacheItem = $zasobyCache->getItem($cacheKey);
        $zasobyZGrupami = $this->pobierzZasobyIdZGrupamiAd();
        $cacheItem->set(serialize($zasobyZGrupami));
        $zasobyCache->save($cacheItem);
    }

    /**
     * Pobiera ID zasobów które mają grupy w AD.
     *
     * @return array
     */
    private function pobierzZasobyIdZGrupamiAd(): array
    {
        $entityManager = $this->getDoctrine();

        $zasobyZGrupaAd = $entityManager
            ->getRepository(Zasoby::class)
            ->findZasobyIdZGrupaAd()
        ;

        $idGrup = [];
        foreach ($zasobyZGrupaAd as $zasob) {
            $idGrup[] = $zasob->getId();
        }

        return $idGrup;
    }

    /**
     * Odbiera zasoby i anuluje wniosek jeżeli jest true opcja `wniosek_do_odebrania_administracyjnego`
     * lub `wniosek_do_anulowania_administracyjnego` oraz jest podany `wniosek_nadanie_odebranie_id`.
     * Do anulowania/odebrania administracyjnego wniosku opcja 'jeden_uzytkownik' musi być true.
     *
     * @param array $zasobDoOdebrania
     * @param string $komentarzOdebrania
     *
     * @return void
     */
    public function odbierzAnulujZasobyAdministracyjnie(array $zasobDoOdebrania, $komentarzOdebrania = null): void
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired([
                'user_zasob'
            ])
            ->setDefaults([
                'wniosek_nadanie_odebranie' => null,
                'jeden_uzytkownik' => false,
                'status_przed_data' => false,
                'procesuj_zasob' => false,
                'wniosek_zakonczony' => false,
                'wniosek_do_odebrania_administracyjnego' => false,
                'wniosek_do_anulowania_administracyjnego' => false,
                'brak_statusow_we_wniosku' => false,
            ]);

        $resolver->resolve($zasobDoOdebrania);

        $status = $zasobDoOdebrania['wniosek_do_odebrania_administracyjnego']?
            WniosekStatus::ODEBRANO_ADMINISTRACYJNIE :
            WniosekStatus::ANULOWANO_ADMINISTRACYJNIE;
        if ($zasobDoOdebrania['jeden_uzytkownik']) {
            $this->statusWnioskuService->setWniosekStatus(
                $zasobDoOdebrania['wniosek_nadanie_odebranie'],
                $status,
                false,
                null,
                $status
            );

            $zasobDoOdebrania['wniosek_nadanie_odebranie']
                ->getWniosek()
                ->zablokujKoncowoWniosek()
            ;
        }

        $status = null !== $komentarzOdebrania? $komentarzOdebrania : $status;
        $userZasob = $zasobDoOdebrania['user_zasob'];

        if (null === $userZasob->getPowodOdebrania()) {
            $userZasob
                ->setWniosekOdebranie(null)
                ->setKtoOdebral($this->currentUser)
                ->setCzyOdebrane(true)
                ->setDataOdebrania(new DateTime())
                ->setPowodOdebrania($status)
                ->setCzyAktywne(false)
            ;

            $entityManager = $this->getDoctrine();
            $entityManager->persist($userZasob);
        }
    }

    /**
     * Metoda nadająca status finalny dla wniosku.
     *
     * @param WniosekNadanieOdebranieZasobow $wniosek
     * @param string $status
     * @param string $komentarz
     * @param bool $odbierzZasoby
     *
     * @return bool
     */
    public function zablokujKoncowoWniosek(
        WniosekNadanieOdebranieZasobow $wniosek,
        $status,
        $komentarz = null,
        $odbierzZasoby = false
    ): bool {
        $statusyKoncowe = array(
            WniosekStatus::ANULOWANO_ADMINISTRACYJNIE,
            WniosekStatus::ODEBRANO_ADMINISTRACYJNIE,
        );

        if (WniosekStatus::ODEBRANO_ADMINISTRACYJNIE === $status && $odbierzZasoby) {
            $userZasoby = $wniosek->getUserZasoby();
            foreach ($userZasoby as $zasob) {
                $dane = [];
                $dane['user_zasob'] = $zasob;
                $dane['jeden_uzytkownik'] = false;
                $dane['wniosek_do_odebrania_administracyjnego'] = WniosekStatus::ODEBRANO_ADMINISTRACYJNIE;

                $this->odbierzAnulujZasobyAdministracyjnie($dane, 'Odebrano administracyjnie');
                if ($this->czyTworzycEntry($zasob)) {
                    $this->stworzEntry([$zasob], $zasob->getSamaccountname());
                }
            }
        }

        if (in_array($status, $statusyKoncowe) && false === $wniosek->getWniosek()->getIsBlocked()) {
            $statusWnioskuService = $this->statusWnioskuService;
            $statusWnioskuService->setWniosekStatus($wniosek, $status, false, null, $komentarz);
            $wniosek->getWniosek()->zablokujKoncowoWniosek();
            $this
                ->doctrine
                ->flush();

            return true;
        }

        return false;
    }

    /**
     * Pobiera wnioski i zasoby które mają być odebrane/anulowane
     * utworzone przed podaną datą.
     *
     * @param WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
     * @param DateTime $dataGraniczna
     *
     * @return array
     */
    private function pobierzWnioskiZasobyDoOdebrania(
        WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow,
        DateTime $dataGraniczna
    ): array {

        if ('10_PODZIELONY' === $wniosekNadanieOdebranieZasobow->getWniosek()->getStatus()->getNazwaSystemowa()) {
            return array();
        }

        if ($wniosekNadanieOdebranieZasobow->getWniosek()->getIsBlocked()) {
            return array();
        }

        $wniosekStatusy = $wniosekNadanieOdebranieZasobow->getWniosek()->getStatusy();

        $wynikSprawdzeniaHistorii = $this->okreslCzyWniosekDoProcesowania($wniosekStatusy, $dataGraniczna);
        $jedenUzytkownikWeWniosku = $this->sprawdzCzyJedenUzytkownikWeWniosku($wniosekNadanieOdebranieZasobow);

        if ($wynikSprawdzeniaHistorii['procesuj_zasob']) {
            $wynikSprawdzeniaHistorii['jeden_uzytkownik'] = $jedenUzytkownikWeWniosku;

            return $wynikSprawdzeniaHistorii;
        }

        return array();
    }

    /**
     * Sprawdza czy we wniosku istnieje tylko jeden użytkownik
     * 1 użytkownik -> wiele zasobów lub
     * 1 użytkownik -> jeden zasób
     *
     * @param WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
     *
     * @return bool
     */
    private function sprawdzCzyJedenUzytkownikWeWniosku(
        WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
    ): bool {
        $pracownicyWeWniosku = explode(',', $wniosekNadanieOdebranieZasobow->getPracownicy());

        return count($pracownicyWeWniosku) === 1? true: false;
    }

    /**
     * Sprwadza statusy w danym wniosku.
     * Jeżeli wniosek ma jeden z końcowych statusów (nadanych) określa go jako wniosek
     * do odrzucenia administracyjnego. Jeżeli jest spełniony jeden z warunków + wystąpił przed podaną datą
     * określamy rodzica tego zasobu (objekt WniosekNadanieOdebranieUprawnien) jako element
     * do dalszego procesowania.
     *
     * @param ArrayCollection $wniosekStatusy
     * @param DateTime $dataGraniczna
     *
     * @return array
     *
     * @todo podobno ma być brany tylko ostatni status, a nie wszystkie?
     */
    private function okreslCzyWniosekDoProcesowania(ArrayCollection $wniosekStatusy, DateTime $dataGraniczna): array
    {
        $statusPrzedData = false;
        $wniosekZakonczony = false;
        $wniosekDoOdebraniaAdministracyjnego = false;
        $wniosekDoAnulowaniaAdministracyjnego = false;
        $brakStatusowWeWniosku = false;
        $procesujZasob = false;
        $istniejeStatusDoPominiecia = false;

        $nazwyStatusowKoncowychNadanych = [
            '11_OPUBLIKOWANY',
            '07_ROZPATRZONY_POZYTYWNIE'
        ];

        $nazwyStatusowDoPominiecia = [
            '08_ROZPATRZONY_NEGATYWNIE',
        ];

        if (0 === count($wniosekStatusy)) {
            $brakStatusowWeWniosku = true;
        }

        foreach ($wniosekStatusy as $status) {
            $nazwaSystemowaStatusu = $status->getStatus()->getNazwaSystemowa();

            if ($status->getCreatedAt() <= $dataGraniczna) {
                $procesujZasob = true;
                $statusPrzedData = true;
            }

            if (in_array($nazwaSystemowaStatusu, $nazwyStatusowDoPominiecia)) {
                $istniejeStatusDoPominiecia = true;
            }

            if (true === $status->getStatus()->getFinished()) {
                $wniosekZakonczony = true;
            }

            if (in_array($nazwaSystemowaStatusu, $nazwyStatusowKoncowychNadanych)) {
                $wniosekDoOdebraniaAdministracyjnego = true;
            }
        }

        if ($istniejeStatusDoPominiecia) {
            $procesujZasob = false;
        }

        if ($procesujZasob && !$wniosekDoOdebraniaAdministracyjnego) {
            $wniosekDoAnulowaniaAdministracyjnego = true;
        }

        return [
            'procesuj_zasob'                            => $procesujZasob,
            'status_przed_data'                         => $statusPrzedData,
            'wniosek_zakonczony'                        => $wniosekZakonczony,
            'wniosek_do_odebrania_administracyjnego'    => $wniosekDoOdebraniaAdministracyjnego,
            'wniosek_do_anulowania_administracyjnego'   => $wniosekDoAnulowaniaAdministracyjnego,
            'brak_statusow_we_wniosku'                  => $brakStatusowWeWniosku
        ];
    }
}
