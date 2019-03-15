<?php

namespace ParpV1\SoapBundle\Services;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundleException\SecurityTestException;
use Exception;
use ParpV1\MainBundle\Helper\AdUserHelper;
use ParpV1\MainBundle\Constants\AdUserConstants;

/**
 * Class LdapAdminService
 *
 * @package ParpV1\SoapBundle\Services
 */
class LdapAdminService
{
    public $pushChanges = false;
    protected $protocol = ''; //"ldap://";
    protected $port = 389;//636;
    protected $debug = 0;
    protected $tokenStorage;
    protected $AdminUser = 'aktywny_dyrektor';
    protected $AdminPass = 'F4UCorsair';
    protected $grupyOU = 'PARP Grupy';
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn ;
    protected $hostId = 3;
    protected $adldap;
    public $lastConnectionErrors = [];
    public $lastEntryId = 0;
    public $lastEntry = null;
    public $output;

    /**
     * LdapAdminService constructor.
     * @param TokenStorage $tokenStorage
     * @param Container $container
     * @param EntityManager $OrmEntity
     * @throws SecurityTestException
     */
    public function __construct(TokenStorage $tokenStorage, Container $container, EntityManager $OrmEntity)
    {
        if (!in_array('PARP_ADMIN', $tokenStorage->getToken()->getUser()->getRoles())) {
            //throw new Exception("Tylko administrator AkD może aktualizować zmiany w AD");
            //echo ""; var_dump(debug_backtrace());
            throw new SecurityTestException('Tylko administrator AkD może aktualizować zmiany w AD');
        }
        error_reporting(0);
        error_reporting(E_ALL ^ E_NOTICE);
        $this->pushChanges = $container->getParameter('pusz_to_ad');
//        ini_set('error_reporting', E_ALL);
        //ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);

        // Attempting fix from http://www.php.net/manual/en/ref.ldap.php#77553
        putenv('LDAPTLS_REQCERT=never');

        $this->doctrine = $OrmEntity;
        $this->container = $container;
        //$this->ad_host = $this->container->getParameter('ad_host'.($this->hostId ? $this->hostId : ""));
        $this->switchServer();
        $this->ad_domain = '@' . $this->container->getParameter('ad_domain');
        $this->AdminUser = $this->container->getParameter('ad_user');
        $this->AdminPass = $this->container->getParameter('ad_password');

        $tab = explode('.', $this->container->getParameter('ad_domain'));
        $env = $this->container->get('kernel')->getEnvironment();

        $this->useradn = $this->container->getParameter('ad_ou');
        if ($env === 'prod') {
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        } else {
            //$this->patch = ',OU=Test ,DC=' . $tab[0] . ',DC=' . $tab[1];
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        }

        $configuration = array(
            //'user_id_key' => 'samaccountname',
            'account_suffix' => $this->ad_domain,
            //'person_filter' => array('category' => 'objectCategory', 'person' => 'person'),
            'base_dn' => 'DC=' . $tab[0] . ',DC=' . $tab[1],
            'domain_controllers' => array($this->container->getParameter('ad_host'),$this->container->getParameter('ad_host2'),$this->container->getParameter('ad_host3')),
            'admin_username' => $this->AdminUser,
            'admin_password' => $this->AdminPass,
            //'real_primarygroup' => true,
            //'use_ssl' => false,
            //'use_tls' => false,
            //'recursive_groups' => true,
            'ad_port' => '389',
            //'sso' => false,
        );

        $this->adldap = new \Adldap\Adldap($configuration);
    }

    /**
     * @param string $error
     */
    public function switchServer($error = '')
    {
        $prevHost = $this->ad_host;
        $this->hostId++;
        if ($this->hostId > 3) {
            $this->hostId = 1;
        }

        $this->ad_host = $this->protocol.$this->container->getParameter('ad_host'.($this->hostId > 1 ? $this->hostId : ''));//.":".$this->port;
        if ($error !== '') {
            $msg = "Nie udało się połączyć z serwerem $prevHost z powodu błędu '$error', przełączam na serwer {$this->ad_host}";
            //print_r("\n".$this->ad_host."\n");
            $this->output->writeln('<error>'.$msg.'</error>');
        }
    }

    /**
     * @param $samaccountname
     *
     * @return array
     */
    public function getUserFromAllAD($samaccountname)
    {
        $ktorzy = 'aktywne';
        $ldap_client = $this->container->get('ldap_service');
        $userNow = $this->getUserFromAD($samaccountname);
        if (count($userNow) == 0) {
            $ktorzy = 'zablokowane';
            $userNow = $ldap_client->getUserFromAD($samaccountname, null, null, $ktorzy);
        }
        if (count($userNow) == 0) {
            $ktorzy = 'nieobecni';
            $userNow = $ldap_client->getUserFromAD($samaccountname, null, null, $ktorzy);
        }
        if (count($userNow) == 0) {
            $ktorzy = 'wszyscyWszyscy';
            $userNow = $ldap_client->getUserFromAD($samaccountname, null, null, $ktorzy);
        }
        return ['user' => $userNow, 'ktorzy' => $ktorzy];
    }

    /**
     * @param null $samaccountname
     * @param null $cnname
     * @param null $query
     *
     * @return array|null
     */
    public function getUserFromAD($samaccountname = null, $cnname = null, $query = null)
    {
        $maxConnections = $this->container->getParameter('maximum_ldap_reconnects');
        $ldapstatus = '';
        $i = 0;
        $result = null;
        do {
            $i++;
            try {
                $result = $this->getUserFromADInt($samaccountname, $cnname, $query);
                $ldapstatus = 'Success';
            } catch (Exception $e) {
                $ldapstatus = ($e->getMessage());
            }
            if ($ldapstatus !== 'Success') {
                $this->switchServer($ldapstatus);
            }
        } while ($ldapstatus !== 'Success' && $i < $maxConnections);
        return $result;
    }

    /**
     * @param null $samaccountname
     * @param null $cnname
     * @param null $query
     *
     * @return array
     *
     * @throws Exception
     */
    public function getUserFromADInt($samaccountname = null, $cnname = null, $query = null)
    {
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        if ($samaccountname) {
            $searchString = '(&(samaccountname='. $samaccountname .')(objectClass=person))';
        } elseif ($cnname) {
            $searchString = $cnname;
        } elseif ($query) {
            $searchString = '(&('.$query.')(objectClass=person))';
        } else {
            $searchString = '(objectClass=person)';
        }

        $search = ldap_search($ldapconn, $userdn, $searchString, array(
            'name',
            'mail',
            'initials',
            'title',
            'info',
            'department',
            'description',
            'division',
            'lastlogon',
            'samaccountname',
            'manager',
            'thumbnailphoto',
            'accountExpires',
            'useraccountcontrol',
            'distinguishedName',
            'cn',
            'memberOf'
        ));

        $tmpResults = ldap_get_entries($ldapconn, $search);

        $ldapstatus = $this->ldapError($ldapconn);

        if ($ldapstatus !== 'Success') {
            $e = new Exception($ldapstatus);
            throw $e;
        }
        ldap_unbind($ldapconn);
        $result = array();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if ($tmpResult['samaccountname']) {
                $result[$i]['isDisabled'] =  $tmpResult['useraccountcontrol'][0] == '546';
                $result[$i]['samaccountname'] = $tmpResult['samaccountname'][0];
                $result[$i]['name'] = isset($tmpResult['name'][0]) ? $tmpResult['name'][0] : '';
                $result[$i]['email'] = isset($tmpResult['mail'][0]) ? $tmpResult['mail'][0] : '';
                $result[$i]['initials'] = isset($tmpResult['initials'][0]) ? $tmpResult['initials'][0] : '';
                $result[$i]['title'] = isset($tmpResult['title'][0]) ? $tmpResult['title'][0] : '';
                $result[$i]['info'] = isset($tmpResult['info'][0]) ? $tmpResult['info'][0] : '';
                $result[$i]['department'] = isset($tmpResult['department'][0]) ? $tmpResult['department'][0] : '';
                $result[$i]['description'] = isset($tmpResult['description'][0]) ? $tmpResult['description'][0] : '';
                $result[$i]['division'] = isset($tmpResult['division'][0]) ? $tmpResult['division'][0] : '';
                $result[$i]['manager'] = isset($tmpResult['manager'][0]) ? $tmpResult['manager'][0] : '';
                $result[$i]['thumbnailphoto'] = isset($tmpResult['thumbnailphoto'][0]) ? $tmpResult['thumbnailphoto'][0] : '';
                $result[$i]['distinguishedname'] = $tmpResult['distinguishedname'][0];
                $result[$i]['cn'] = $tmpResult['cn'][0];
                $result[$i]['memberOf'] = $this->parseMemberOf($tmpResult);
                $i++;
            }
        }

        return $result;
    }

    /**
     * @param $res
     *
     * @return array
     */
    protected function parseMemberOf($res)
    {
        $ret = array();
        $gr = isset($res['memberof']) ? $res['memberof']: array();
        foreach ($gr as $k => $g) {
            if ($k !== 'count') {
                $p = explode(',', $g);
                $p2 = str_replace('CN=', '', $p[0]);
                $ret[] = $p2;
            }
        }
        return $ret;
    }

    /**
     * @param $ldapUser
     * @param $person - dane z entry
     *
     * @return string
     */
    public function saveEntity($ldapUser, $person)
    {
        if (null === $ldapUser) {
            $ldapUser = $this->getUserFromAD($person->getSamaccountname());
        }
        $adUserHelper = new AdUserHelper($ldapUser);

        $this->lastEntryId = $person->getId();
        $this->lastEntry = $person;
        $this->lastConnectionErrors = [];
        $this->container->get('adcheck_service')->checkIfUserCanBeEdited($person->getSamaccountname());
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $this->AdminUser . $ldapdomain, $this->AdminPass);

        $dn = $ldapUser;
        //zmieniamy ze jednak bierze dn z aktualnego usera

        $userNowData = $this->getUserFromAllAD($person->getSamaccountname());
        $userAD = $userNowData['user'];
        $ktorzy = $userNowData['ktorzy'];
        $dn = $userAD[0]['distinguishedname'];
        $entry = array();

        if ($person->getAccountExpires()) {
            // Dla roku 2000 i 3000 data wygaśnięcia jest zerowana.
            if ($person->getAccountExpires()->format('Y') === '2000' || $person->getAccountExpires()->format('Y') === '3000') {
                $entry['accountExpires'] = 0;
            } else {
                $d = $this->unixToLdap($person->getAccountExpires()->getTimestamp());
                $entry['accountExpires'] = $d;
            }
        }


        if ($person->getManager() !== $adUserHelper::getPrzelozony(false)) {
            $manager = $person->getManager();
            if ($manager === 'BRAK') {
                $entry['manager'] = [];
            } elseif (strstr($manager, 'CN=') === false) {
                // znajdz sciezke przelozonego
                $cn = preg_replace('/\\\\-/', '-', preg_quote($manager));

                $searchString = '(&(cn='. $cn .')(objectClass=person))';

                $search = ldap_search($ldapconn, $userdn, $searchString, array(
                    'name',
                    'initials',
                    'title',
                    'info',
                    'department',
                    'description',
                    'division',
                    'lastlogon',
                    'samaccountname',
                    'manager',
                    'thumbnailphoto',
                    'accountExpires',
                    'useraccountcontrol',
                    'distinguishedName',
                ));
                $tmpResults = ldap_get_entries($ldapconn, $search);
                $entry['manager'] = $tmpResults[0]['distinguishedname'][0];
                if ($entry['manager'] === null) {
                    unset($entry['manager']);
                }
            } elseif (strstr($manager, 'CN=') !== false) {
                $entry['manager'] = $person->getManager();
            }
        }



        if ($person->getTitle() !== $adUserHelper::getStanowisko()) {
            $entry['title'] = $this->mapowanieStanowisk($person->getTitle());
        }

        $entry['initials'] = array();
        /*
        if ($person->getInitials()) {
            //hack by dalo sie puste inicjaly wprowadzic,
            //TODO: trzeba zmienic bo jednak beda generowane !!!!
            if ($person->getInitials() === 'puste' || $person->getInitials() === '' || $person->getInitials() ===
                'BRAK'
            ) {
                //unset($entry['initials']);
                $entry['initials'] = array();
            } else {
                $entry['initials'] = $person->getInitials();
            }
        }*/

        //$department = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        $department =  $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneBy(['name' => trim($person->getDepartment()), 'nowaStruktura' => true]);


        if ($person->getDepartment() !== $adUserHelper::getDepartamentNazwa()) {
            $entry['department'] = $person->getDepartment();
            if (!empty($department)) {
                $entry['description'] = $department->getShortname();
            }
            //$departmentOld = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($userAD[0]['department']);
            $departmentOld =  $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneBy(['name' => trim($userAD[0]['department']), 'nowaStruktura' => true]);
            if (!$departmentOld) {
                //szuka z nazwa stara struktura
                $departmentOld =  $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneBy(['name' => trim($userAD[0]['department']).
                    ' - stara struktura', 'nowaStruktura'                                                         => false]);
            }
            if (!$departmentOld) {
                echo("Nie znalazl starego departamentu '".$userAD[0]['samaccountname']."' - '".$userAD[0]['department']."'");
            } else {
                $person->addGrupyAD($departmentOld, '-');
                $grupyNaPodstawieSekcjiOrazStanowiska = $this->container->get('ldap_service')->getGrupyUsera($userAD[0], $departmentOld->getShortname(), $userAD[0]['division']);
                $person->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '-');
                $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);

                //pobieramy ponownie usera bo musimy mu zdjac grupy
                $userNowData = $this->getUserFromAllAD($person->getSamaccountname());
                $userAD = $userNowData['user'];
            }
            //jesli zmiana departamnentu dodajemy nowe grupy AD
            if (!$department) {
                echo("Nie znalazl nowego departamentu '".$userAD[0]['samaccountname']."' - '".$userAD[0]['department']."'");
            } else {
                $person->addGrupyAD($department, '-');
                if ($person->getTitle()) {
                    //musimy zmienic stanowisko w $userAD aby dobrze wybral grupy uprawnien
                    $userAD[0]['title'] = $this->mapowanieStanowisk($person->getTitle());
                }

                $grupyNaPodstawieSekcjiOrazStanowiska = $this->container->get('ldap_service')->getGrupyUsera(
                    $userAD[0],
                    $department->getShortname(),
                    ''
                );
                $person->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '+');
                //$person->setInfo("SEKCJA DO UZUPEŁNIENIA PRZEZ KADRY");
                $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);
            }
        }




       /* if ($person->getDivision() != null) {
            if ($person->getDivision() === 'BRAK' || $person->getDivision() === '') {
                $entry['division'] = []; //"n/d";
                //$entry['division'] = []; //"n/d";
                //unset($entry['info']);
                //unset($entry['division']);
            } else {
                //$entry['info'] = "SEKCJA DO UZUPEŁNIENIA PRZEZ KADRY";
                $entry['division'] = $person->getDivision(); //"";
            }
        }*/

        if ($person->getInfo() != null) {
            if ($person->getInfo() === 'BRAK' || $person->getInfo() === '') {
                $entry['info'] = []; //"n/d";
                //$entry['division'] = []; //"n/d";
                //unset($entry['info']);
                //unset($entry['division']);
            } elseif ($person->getInfo() === 'BRAK' || $person->getInfo() === 'SEKCJA DO UZUPEŁNIENIA PRZEZ KADRY') {
                //$entry['info'] = "SEKCJA DO UZUPEŁNIENIA PRZEZ KADRY";
                $entry['info'] = []; //"";
            } else {
                $entry['info'] = $person->getInfo();
                // obsłuz miane atrybuty division
                $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
                $entry['division'] =  $section->getShortname();//getShortname();
            }
        }



        if (null !== $person->getIsDisabled()) {
            $entry['useraccountcontrol'][0] = $person->getIsDisabled() ? 514 : 512; //546 : 544;
            $sn = 'Konto aktywowane';
            if (!empty($department)) {
                $sn = $department->getShortname();
            }

            if ($person->getIsDisabled()) {
                $entry['description'] = $person->getDisableDescription();
                if ($person->getDisableDescription() === AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY) {
                    $grupyWszystkie = $userAD[0]['memberOf'];
                    $person->addGrupyAD($grupyWszystkie, '-');
                }
            } else {
                $entry['description'] = $sn;
            }
        }


        //print_r($entry); die();
        if (count($entry) > 0) {
            //var_dump($dn, $entry);
            $res = $this->ldapModify($ldapconn, $dn, $entry);


            $error = $this->ldapError($ldapconn);
            $errno = $this->ldapErrno($ldapconn);

            $ldapstatus = $this->ldapError($ldapconn);
            if ($ldapstatus !== 'Success') {
                if ($this->debug) {
                    die('bbb '.$ldapstatus);
                }
                return $ldapstatus;
            }
        }

        if ($person->getMemberOf()) {
            $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);
            $ldapstatus = $this->ldapError($ldapconn);
        }
        // zmiana kontenera - obsługujemy nie modyfikacja
        // zmiana departamentu musi byc ostnia operacją ponieważ zmienimi rownież
        // kontener pracownika. Jezeli zmodyfikujemy go wczecniej to pozowatłe operacje mogą
        // nie znaleśc obiektu w ad (zmieniamy przeciez distinguishedName!).
        if ($person->getDepartment() !== $adUserHelper::getDepartamentNazwa()) {
            // zmien ds pracownika
            //$userAD = $this->getUserFromAD($person->getSamaccountname());
            $parent = 'OU=' . $entry['description'] . ',' . $userdn;

            $cn = $userAD[0]['name'];
            //na koncu razem z kontenerem zmieniamy cn bo wtedy nic nie znajdzie w ad
            if ($person->getCn()) {
                $cn = $person->getCn();
            }
            $b = $this->ldapRename($ldapconn, $person->getDistinguishedName(), 'CN='. $cn, $parent, true);

            $ldapstatus = $this->ldapError($ldapconn);
        } elseif ($person->getCn() !== $adUserHelper::getImieNazwisko()) {
            //zmieniamy tylko cn
            $cn = $person->getCn();

            $this->changePrimaryEmail($person->getSamaccountname(), $this->container->get('samaccountname_generator')->generateSamaccountnamePoZmianieNazwiska($person->getCn()));
            $b = $this->ldapRename($ldapconn, $person->getDistinguishedName(), 'CN='. $cn, null, true);

            $ldapstatus = $this->ldapError($ldapconn);
        }
        // przenosimy konto do OU=Zablokowane
        if ($person->getDisableDescription() === AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY) {
            $this->ldapModDel($ldapconn, $person->getDistinguishedName(), array("manager" => array()));
            $newparent = str_replace('OU=Zespoly_2016,', 'OU=Zablokowane,', $userdn);
            $this->ldapRename($ldapconn, $person->getDistinguishedName(), 'CN='. $userAD[0]['name'], $newparent, true);
            $ldapstatus = $this->ldapError($ldapconn);
        }
        // przenosimy konto do OU=Nieobecni
        if ($person->getDisableDescription() === AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC) {
            $newparent = str_replace('OU=Zespoly_2016,', 'OU=Nieobecni,', $userdn);
            $this->ldapRename($ldapconn, $person->getDistinguishedName(), 'CN='. $userAD[0]['name'], $newparent, true);
            $ldapstatus = $this->ldapError($ldapconn);
        }
        ldap_unbind($ldapconn);

        // po zapisaniu zmian w AD czyścimy cache
        $ldap = $this->container->get('ldap_service');
        $ldap->clearLdapCache();

        //to wyrzucone bo nie zawsze zapisuje (jak nie wypoycha tylko pokazuje to nie ma zapisu) wiec flush jest w command!!!
        //$person->setIsImplemented(1);
        //$this->doctrine->persist($person);
        //$this->doctrine->flush();
        return $ldapstatus;
    }

    /**
     * @param $grupa
     *
     * @return array|bool
     */
    public function getGrupa($grupa)
    {
        try {
            $grupaReturn = $this->adldap->group()->find($grupa);

            return $grupaReturn;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param $person
     * @param $userAD
     * @param $dn
     * @param $userdn
     * @param $ldapconn
     */
    public function addRemoveMemberOf($person, &$userAD, $dn, $userdn, $ldapconn)
    {
        if ($person->getMemberOf() !== '') {
            $grupy = explode(',', $person->getMemberOf());
            foreach ($grupy as $grupa) {
                if ($grupa !== '') {
                    $znak = substr($grupa, 0, 1);
                    $g = substr($grupa, 1);
                    $this->output->writeln('<comment>'.(($znak) == '+' ? 'Dodanie do' : 'Usuniecie z').' '.($znak).
                        ' grupy  '.$g.
                        '</comment>'); //." , czy user w niej jest: ".in_array($g, $userAD[0]['memberOf'])."</comment>");

                    $grupa = $this->getGrupa($g);
                    if (!$grupa) {
                        $error = 'Nie istnieje w AD grupa "'.$g.'"!!!';
                        $this->output->writeln('<error>'.$error.'</error>');
                        $this->lastConnectionErrors[] = [
                            'function' => 'addRemoveMemberOf',
                            'error' => $error,
                            'errorno' => 77771,
                            'lastEntryId' => $person->getId(),
                            'lastEntry' => $person->getId()
                        ];
                    } else {
                        $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                        //var_dump($g, $dn, $addtogroup); die();
                        //var_dump($g, $userAD[0]['memberOf']); //die();
                        $akcja = true;
                        if ($znak == '+' && !in_array($g, $userAD[0]['memberOf'])) {
                            $this->ldapModAdd($ldapconn, $addtogroup, array('member' => $userAD[0]['distinguishedname'] ));
                        } elseif ($znak == '-' && in_array($g, $userAD[0]['memberOf'])) {
                            $this->ldapModDel($ldapconn, $addtogroup, array('member' => $userAD[0]['distinguishedname'] ));
                        } elseif ($znak == '-') {
                            $this->output->writeln('<error>Nie usunięto użytkownika z grupy '.$g.' bo w niej nie był!</error>');
                            $akcja = true;
                        } elseif ($znak == '+') {
                            $this->output->writeln('<error>Nie dodano użytkownika do grupy '.$g.' bo w niej już jest!</error>');
                            $akcja = true;
                        } else {
                            $akcja = true;
                        }
                        if ($akcja) {
                            $ldapstatus = $this->ldapError($ldapconn);
                            //echo "<br>ldapstatus $ldapstatus";
                            if ($ldapstatus === 'Success') {
                                if ($znak == '+') {
                                    //dodac do memberOf
                                    //echo "<br>Dodaje memberOf $g";
                                    $userAD[0]['memberOf'][] = $g;
                                } else {
                                    //zdjac z memberOf
                                    //echo "<br>Zjemuje memberOf $g";
                                    $userAD[0]['memberOf'] = array_diff($userAD[0]['memberOf'], array($g));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $newPassword
     *
     * @return mixed
     */
    public function pwdEncryption($newPassword)
    {

        $newPassword = '"'. $newPassword .'"';
        $len = strlen($newPassword);
        $newPassw = '';
        for ($i = 0; $i < $len; $i++) {
            $newPassw .= "{$newPassword{$i}}\000";
        }
        $userdata['unicodePwd'] = $newPassw;

        return $userdata;
    }

    /**
     * @param $name
     *
     * @param $samaccountname
     */
    public function sendMailAboutNewUser($name, $samaccountname)
    {
        $mails = ['marcin_lipinski@parp.gov.pl','dorota_tymanowska@parp.gov.pl'];

        $ldap = $this->container->get('ldap_service');
        $user = $ldap->getUserFromAD($samaccountname);

        $view =
            'Dnia '.
            date('Y-m-d').
            " został utworzony nowy użytkownik '".
            $name.
            "' o loginie '".
            $samaccountname.
            "', utwórz mu pocztę pliz :)";
        $view .=
            "<br><br>Pozostałe dane: <ul><li>stanowisko: ".
            $user[0]['title'] . '</li><li>department: '.
            $user[0]['department'].
            ' [' . $user[0]['description'].
            ']</li><li>sekcja: '.
            $user[0]['info'].
            ' [' . $user[0]['division'].
            ']</li><li>e-mail wewnętrzny: '.
            $samaccountname.
            '@parp.gov.pl</li></ul>';

        $message = \Swift_Message::newInstance()
            ->setSubject('Nowy użytkownik w AkD')
            ->setFrom('intranet@parp.gov.pl')
            ->setTo($mails)
            ->setBody($view)
            ->setContentType('text/html');

        $this->container->get('mailer')->send($message);
    }

    /**
     * @param $person
     *
     * @return string
     */
    public function createEntity($person)
    {
        $this->lastEntryId = $person->getId();
        $this->lastEntry = $person;
        $this->lastConnectionErrors = [];
        $this->container->get('adcheck_service')->checkIfUserCanBeEdited($person->getSamaccountname());

        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        try {
            $accountExpires = $person->getAccountExpires();
            $dn = $person->getDistinguishedName();

            $entry = array();
            $entry['cn'] = $person->getCn();

            if (!empty($accountExpires)) {
                if ($accountExpires->format('Y') === '2000' || $accountExpires->format('Y') === '3000') {
                    $entry['accountExpires'] = 0;
                } else {
                    $entry['accountExpires'] = $this->unixToLdap($accountExpires->getTimestamp());
                }
            }

            $manager = $person->getManager();
            if (!empty($manager)) {
                // znajdz sciezke przelozonego
                $cn = $manager;
                $searchString = '(&(cn=' . $cn . ')(objectClass=person))';

                $search = ldap_search($ldapconn, $userdn, $searchString, array(
                    'name',
                    'initials',
                    'title',
                    'info',
                    'department',
                    'description',
                    'division',
                    'lastlogon',
                    'samaccountname',
                    'manager',
                    'thumbnailphoto',
                    'accountExpires',
                    'useraccountcontrol',
                    'distinguishedName',
                ));
                $tmpResults = ldap_get_entries($ldapconn, $search);
                $entry['manager'] = $tmpResults[0]['distinguishedname'][0];
            }

            $tab = explode(' ', $entry['cn']);
            $entry['sn'] = count($tab) > 0 ? $tab[0] : '';
            $entry['givenName'] = $tab[1];
            $entry['name'] = $entry['cn'];
            $entry['displayName'] = $entry['cn'];
            $entry['userPrincipalName'] = $person->getSamaccountname() . $this->ad_domain;
            $entry['department'] = $person->getDepartment();
            $entry['division'] = $person->getDivision();
            $entry['title'] = $person->getTitle();
            $entry['distinguishedname'] = $person->getDistinguishedname();
            $entry['initials'] = $person->getInitials();
            $entry['samaccountname'] = $person->getSamaccountname();
            $entry['objectClass']['0'] = 'top';
            $entry['objectClass']['1'] = 'person';
            $entry['objectClass']['2'] = 'organizationalPerson';
            $entry['objectClass']['3'] = 'user';
            $entry['company'] = 'Polska Agencja Rozwoju Przedsiębiorczości';
            $entry['streetAddress'] = 'ul. Pańska 81/83';
            $entry['wWWHomePage'] = 'www.parp.gov.pl';
            $entry['co'] = 'Poland';
            $entry['c'] = 'PL';
            $entry['l'] = 'Warszawa';
            $entry['postalCode'] = '00-834';
            $entry['extensionAttribute11'] = 'exchange.parp.gov.pl';

            foreach ($entry as $key => $value) {
                if (!is_array($value)) {
                    if (strlen($value) == 0) {
                        //wywalamy puste wartosci do invalid syntax
                        unset($entry[$key]);
                    }
                }
            }

            $entry['useraccountcontrol'] = 544; // włączenie konta i wymuszenie zmiany hasla
            $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
            if ($section) {
                $entry['division'] = $section->getName();
            } else {
                $entry['division'] = 'n/d';
            }
            $description = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());

            if (!empty($description)) {
                $entry['description'] = $description->getShortname();
                $entry['extensionAttribute14'] = $description->getShortname();
            }
            $newuser_plaintext_password = 'F4UCorsair';
            //$entry['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($newuser_plaintext_password)));
            if ($this->debug) {
                echo '<pre>';
                print_r($dn);
                print_r($entry);
                echo '</pre>';
                die();
            }
            $this->ldapAdd($ldapconn, $dn, $entry);
            if ($description !== null) {
                $grupyNaPodstawieSekcjiOrazStanowiska = $this->container->get('ldap_service')->getGrupyUsera($entry, $description->getShortname(), $userAD[0]['division']);
                //print_r($grupyNaPodstawieSekcjiOrazStanowiska); die();
                $person->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '+');
                $data = [['memberOf' => []]];
                $this->addRemoveMemberOf($person, $data, $dn, $userdn, $ldapconn);
            }
            //$this->addRemoveMemberOf($person, [["memberOf" => []]], $dn, $userdn, $ldapconn);

            $this->sendMailAboutNewUser($entry['name'], $entry['samaccountname']);
        } catch (Exception $exception) {
            return "Error";
        }

        $ldapstatus = $this->ldapError($ldapconn);

        if ($this->debug) {
            die('koniec bo debug ' . $ldapstatus);
        }

        ldap_unbind($ldapconn);

        // po zapisaniu zmian w AD czyścimy cache
        $ldap = $this->container->get('ldap_service');
        $ldap->clearLdapCache();

        //to wyrzucone bo nie zawsze zapisuje (jak nie wypoycha tylko pokazuje to nie ma zapisu) wiec flush jest w command!!!
        //$person->setIsImplemented(1);
        //$this->doctrine->persist($person);
        //$this->doctrine->flush();
        return $ldapstatus;
    }

    /**
     * @param $ldap_ts
     *
     * @return float|int
     */
    protected function LDAPtoUnix($ldap_ts)
    {
        return ($ldap_ts / 10000000) - 11644473600;
    }

    /**
     * @param $unix_ts
     *
     * @return string
     */
    protected function unixToLdap($unix_ts)
    {
        return sprintf('%.0f', ($unix_ts + 11644473600) * 10000000);
    }

    public function syncDepartamentsOUs()
    {

        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;
        $userdn = str_replace('OU=Zespoly,', '', $userdn);
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $em = $this->container->get('doctrine')->getManager();
        $deps = $em->getRepository('ParpMainBundle:Departament')->findByNowaStruktura(1);
        foreach ($deps as $dep) {
            if ($dep->getShortname()) {
                $filter= '(objectClass=organizationalunit)';
                $justthese = array('dn', 'ou');

                //$ou = $this->adldap->folder()->find($dep->getOuAD().", ".$userdn);

                //var_dump($ou, $dep->getOuAD().", ".$userdn, $userdn, $filter, $justthese);
                $info = [];
                try {
                    $sr=ldap_search($ldapconn, $dep->getOuAD().', '.$userdn, $filter, $justthese);
                    $info = ldap_get_entries($ldapconn, $sr);
                    ldap_free_result($sr);
                } catch (Exception $e) {
                    echo 'dodaje biuro '.$dep->getOuAD().', '.$userdn.'!!!!';
                }
                if ($info['count'] > 0) {
                } else {
                    echo 'dodaje biuro !!!!';
                    $ldapstatus2 = $this->ldapError($ldapconn);
                    $this->ldapAdd($ldapconn, $dep->getOuAD().', '.$userdn, array(
                        'ou' => $dep->getShortname(),
                        'objectClass' => 'organizationalUnit',
                        'l' => 'location'
                    ));
                    $ldapstatus = $this->ldapError($ldapconn);
                    //var_dump("Nie ma OU", $userdn."<br>", $info["count"]."<br>".$ldapstatus2."<br>".$ldapstatus."<br>".$res."<br>"."<br>");
                }
            }
        }

        ldap_unbind($ldapconn);
    }

    /**
     * @param $dn
     */
    public function deleteEntity($dn)
    {
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;
        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $this->ldapDelete($ldapconn, $dn);
    }

    /**
     * @return resource
     */
    public function prepareConnection()
    {
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }

        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;
        $ldapbind = ldap_bind($ldapconn, $this->AdminUser . $ldapdomain, $this->AdminPass);

        return $ldapconn;
    }

    /**
     * @param $sam
     * @param $email
     */
    public function changePrimaryEmail($sam, $email)
    {
        $this->adldap->exchange()->addAddress($sam, $email, true);
        $this->adldap->exchange()->primaryAddress($sam, $email);
    }

    /**
     * @param $link_identifier
     *
     * @param $funcname
     */
    public function zalogujBlad($link_identifier, $funcname)
    {
        $this->lastConnectionErrors[] = [
            'function' => $funcname,
            'error' => $this->ldapError($link_identifier),
            'errorno' => $this->ldapErrno($link_identifier),
            'lastEntryId' => $this->lastEntryId,
            'lastEntry' => $this->lastEntry
        ];
    }

    /**
     * @param $link_identifier
     * @param $dn
     * @param $entry
     */
    public function ldapModify($link_identifier, $dn, $entry)
    {
        $poszlo = false;
        if ($this->pushChanges) {
            try {
                //throw new Exception('a');
                ldap_modify($link_identifier, $dn, $entry);
                $poszlo = true;
            } catch (Exception $e) {
                $this->output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }

        if (!$this->pushChanges || !$poszlo) {
            $data = [];
            foreach ($entry as $k => $v) {
                $data[] = "'$k' = '$v'";
            }
            $this->output->writeln('<error>wykonuje funkcje ldapModify</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
            $this->output->writeln('<error>entry: '.implode(', ', $data).'</error>');
        }

        $this->zalogujBlad($link_identifier, 'ldapModify');
    }

    /**
     * @param $link_identifier
     * @param $dn
     * @param $newrdn
     * @param $newparent
     * @param $deleteoldrdn
     */
    public function ldapRename($link_identifier, $dn, $newrdn, $newparent, $deleteoldrdn)
    {
        $poszlo = false;
        if ($this->pushChanges) {
            try {
                ldap_rename($link_identifier, $dn, $newrdn, $newparent, $deleteoldrdn);
                $poszlo = true;
            } catch (Exception $e) {
                $this->output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }
        if (!$this->pushChanges || !$poszlo) {
            $this->output->writeln('<error>wykonuje funkcje ldapRename</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
            $this->output->writeln('<error>newrdn: '.$newrdn.'</error>');
            $this->output->writeln('<error>newparent: '.$newparent.'</error>');
            $this->output->writeln('<error>deleteoldrdn: '.$deleteoldrdn.'</error>');
        }

        $this->zalogujBlad($link_identifier, 'ldapRename');
    }

    /**
     * @param $link_identifier
     * @param $dn
     * @param $entry
     */
    public function ldapModAdd($link_identifier, $dn, $entry)
    {
        $poszlo = false;
        if ($this->pushChanges) {
            try {
                ldap_mod_add($link_identifier, $dn, $entry);
                $poszlo = true;
            } catch (Exception $e) {
                $this->output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }
        if (!$this->pushChanges || !$poszlo) {
            $data = [];
            foreach ($entry as $k => $v) {
                $data[] = "'$k' = '$v'";
            }
            $this->output->writeln('<error>wykonuje funkcje ldapModAdd</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
            $this->output->writeln('<error>entry: '.implode(', ', $data).'</error>');
        }

        $this->zalogujBlad($link_identifier, 'ldapModAdd');
    }

    /**
     * @param $link_identifier
     * @param $dn
     * @param $entry
     */
    public function ldapModDel($link_identifier, $dn, $entry)
    {
        $poszlo = false;
        if ($this->pushChanges) {
            try {
                ldap_mod_del($link_identifier, $dn, $entry);
                $poszlo = true;
            } catch (Exception $e) {
                $this->output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }
        if (!$this->pushChanges || !$poszlo) {
            $data = [];
            foreach ($entry as $k => $v) {
                $data[] = "'$k' = '$v'";
            }
            $this->output->writeln('<error>wykonuje funkcje ldapModDel</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
            $this->output->writeln('<error>entry: '.implode(', ', $data).'</error>');
        }

        $this->zalogujBlad($link_identifier, 'ldapModDel');
    }

    /**
     * @param $linkIdentifier
     * @param $dn
     * @param $entry
     */
    public function ldapAdd($linkIdentifier, $dn, $entry)
    {
        $poszlo = false;
        if ($this->pushChanges) {
            try {
                ldap_add($linkIdentifier, $dn, $entry);
                $poszlo = true;
            } catch (Exception $e) {
                $this->output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }
        if (!$this->pushChanges || !$poszlo) {
            $data = [];
            foreach ($entry as $k => $v) {
                $data[] = "'$k' = '$v'";
            }
            $this->output->writeln('<error>wykonuje funkcje ldapAdd</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
            $this->output->writeln('<error>entry: '.implode(', ', $data).'</error>');
        }

        $this->zalogujBlad($linkIdentifier, 'ldapAdd');
    }

    /**
     * @param $linkIdentifier
     * @param $dn
     */
    public function ldapDelete($linkIdentifier, $dn)
    {
        if ($this->pushChanges) {
            echo 'kasuje dn '.$dn;
            //ldapDelete($link_identifier, $dn);
        } else {
            $this->output->writeln('<error>wykonuje funkcje ldapDelete</error>');
            $this->output->writeln('<error>dn: '.$dn.'</error>');
        }

        $this->zalogujBlad($linkIdentifier, 'ldapDelete');
    }

    /**
     * @param $ldapconn
     * @return string
     */
    public function ldapError($ldapconn)
    {
        return ldap_error($ldapconn);
    }

    /**
     * @param $ldapconn
     * @return int
     */
    public function ldapErrno($ldapconn)
    {
        return ldap_errno($ldapconn);
    }

    /**
     * @param $stanowisko
     * @return mixed
     */
    public function mapowanieStanowisk($stanowisko)
    {
        $mapa = [
            'rzecznik prasowy' => 'rzecznik prasowy PARP'
        ];
        if (isset($mapa[$stanowisko])) {
            $stanowisko = $mapa[$stanowisko];
        }

        return $stanowisko;
    }
}
