<?php

namespace ParpV1\SoapBundle\Services;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Debug\Exception\ContextErrorException as DebugContextErrorException;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\Section;
use ParpV1\LdapBundle\Connection\LdapConnection;
use ParpV1\LdapBundle\Service\LdapFetch;
use Symfony\Component\VarDumper\VarDumper;

class LdapService
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var LdapFech
     */
    private $ldapFetch;

    protected $dodatkoweOpcje = 'ekranEdycji';
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn;
    protected $_ouWithGroups = 'PARP Grupy';
    public $adldap;
    public $adldapSeparate;
    protected $_userCache = null;
    protected $zmianyDoWypchniecia = null;
    protected $ADattributes = array(
        'name',
        'initials',
        'title',
        'mail',
        'info',
        'department',
        'description',
        'division',
        'lastlogon',
        'samaccountname',
        'manager',
        'thumbnailphoto',
        'accountExpires',
        'accountexpires',
        'useraccountcontrol',
        'distinguishedName',
        'cn',
        'mailnickname',
        'memberOf',
        'extensionAttribute10',
    );

    public function __construct(Container $container, CacheItemPoolInterface $cacheItemPool, LdapConnection $ldapConnection, LdapFetch $ldapFetch)
    {
        $this->container = $container;
        $this->ad_host = $this->container->getParameter('ad_host');
        $this->ad_domain = '@' . $this->container->getParameter('ad_domain');
        $this->ldapFetch = $ldapFetch;
        $tab = explode('.', $this->container->getParameter('ad_domain'));

        $env = $this->container->get('kernel')->getEnvironment();

        $this->useradn = $this->container->getParameter('ad_ou');
        if ($env === 'prod') {
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        } else {
            //$this->patch = ',OU=Test ,DC=' . $tab[0] . ',DC=' . $tab[1];
            $this->patch = ' ,DC=' . $tab[0] . ',DC=' . $tab[1];
        }
        //die($this->patch);
        $this->cache = $cacheItemPool;

        $configuration = array(
            //'user_id_key' => 'samaccountname',
            'account_suffix' => $this->ad_domain,
            //'person_filter' => array('category' => 'objectCategory', 'person' => 'person'),
            'base_dn' => 'DC=' . $tab[0] . ',DC=' . $tab[1],
            'domain_controllers' => array(
                $this->container->getParameter('ad_host'),
                $this->container->getParameter('ad_host2'),
                $this->container->getParameter('ad_host3'),
            ),
            'admin_username' => $this->container->getParameter('ad_user'),
            'admin_password' => $this->container->getParameter('ad_password'),
            //'real_primarygroup' => true,
            //'use_ssl' => false,
            //'use_tls' => false,
            //'recursive_groups' => true,
            'ad_port' => '389',
                //'sso' => false,
        );
       // $this->adldap = new \Adldap\Adldap($configuration);
        $this->adldap = $ldapConnection->getAdLdap();
    }

    public function getAllManagersFromAD()
    {
        $res = $this->getAllFromAD();
        $ret = [];
        $managerTitles = ['Dyrektor', 'Dyrektor (p.o.)', 'Zastępca Dyrektora', 'Zastępca Dyrektora (p.o.)'];
        foreach ($res as $r) {
            if (in_array($r['title'], $managerTitles, true)) {
                $ret[] = $r;
            }
        }

        //echo "<pre>"; print_r($ret); die();
        return $ret;
    }

    public function getAllFromADforCombo($tezNieobecni = false, $justDump = false, $struktura = null)
    {
        $us = $this->getAllFromAD($tezNieobecni, $justDump, $struktura);
        $ret = [];
        foreach ($us as $u) {
            $ret[$u['samaccountname']] = $u['name'];
        }

        return $ret;
    }

    protected function parseZmianyUsera($u, $zmiany)
    {
        $noweAttr = [];
        foreach ($zmiany[$u['samaccountname']] as $z) {
            if ($z->getAccountExpires()) {
                $noweAttr['accountExpires'] = $z->getAccountExpires()->format('Y-m-d');
            }
            if ($z->getDivision()) {
                $noweAttr['division'] = $z->getDivision();
            }
            if ($z->getManager()) {
                $noweAttr['manager'] = $z->getManager();
            }
            if ($z->getTitle()) {
                $noweAttr['title'] = $z->getTitle();
            }
            if ($z->getInfo()) {
                $noweAttr['info'] = $z->getInfo();
            }
            if ($z->getDisableDescription()) {
                $noweAttr['description'] = $z->getDisableDescription();
            }
        }

        return $noweAttr;
    }

    public function getAllFromAD($tezNieobecni = false, $justDump = false, $struktura = null, $noCache = false)
    {
        $cache = $this->cache;
        $cacheKey = 'ad_users_' . $tezNieobecni . '_' . $justDump . '_' . $struktura;
        $cacheItem = $cache->getItem($cacheKey);

        if ($cacheItem->isHit() && false === $noCache) {
            return unserialize($cacheItem->get());
        }

        $adUsers = $this->getAllFromADIntW($tezNieobecni, $justDump, $struktura);
        $cacheItem->set(serialize($adUsers));
        $cache->save($cacheItem);

        return $adUsers;
    }

    /**
     * Pobiera wszystkich użytkowników z AD (tylko nazwa użytkownika + imię nazwisko)
     *
     * @return array
     */
    public function getAllUsersNamesLogins(): array
    {
        $users = [];
        $adUsers = $this->getAllFromAD();
        foreach ($adUsers as $adUser) {
            $users[$adUser['samaccountname']] = $adUser['name'];
        }

        return $users;
    }

    public function getAllFromADIntW($ktorych = 'aktywni', $justDump = false, $struktura = null)
    {
        //wywlam na czas odbierania $this->zmianyDoWypchniecia = $this->container->get('doctrine')->getManager()->getRepository('ParpMainBundle:Entry')->findByIsImplemented(0, ['samaccountname' => 'ASC', 'id' => 'ASC']);
        $userdn = $this->useradn . $this->patch;

        if ($struktura == '2016') {
            $userdn = str_replace('OU=Zespoly,', 'OU=Zespoly_2016,', $userdn);
        } else {
            if ($struktura == 'stara') {
                $userdn = str_replace('OU=Zespoly_2016,', 'OU=Zespoly,', $userdn);
            }
        }
        if ($ktorych == 'wszyscyWszyscy') {
            $userdn = str_replace(
                'OU=Zespoly_2016, OU=PARP Pracownicy ,',
                '',
                str_replace(
                    'OU=Zespoly_2016,OU=PARP Pracownicy ,',
                    '',
                    $userdn
                )
            );
            //die($userdn);
        } elseif ($ktorych == 'wszyscy') {
            $userdn = str_replace('OU=Zespoly_2016,', '', $userdn);
        } elseif ($ktorych == 'zablokowane') {
            $userdn = str_replace('OU=Zespoly_2016,', 'OU=Zablokowane,', $userdn);
        } elseif ($ktorych == 'nieobecni') {
            $userdn = str_replace('OU=Zespoly_2016,', 'OU=Nieobecni,', $userdn);
        }


        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $letters_array = str_split($letters);

        $tmpResults = array();
        foreach ($letters_array as $letter) {
            $search = ldap_search(
                $ldapconn,
                $userdn,
                '(&(samaccountname=' . $letter . '*)(objectClass=person))',
                $this->ADattributes
            );
            $results = ldap_get_entries($ldapconn, $search);
            $tmpResults = array_merge($tmpResults, $results);
        }
        ldap_bind($ldapconn);


        $result = $this->parseResults($tmpResults);
        if ($justDump) {
            foreach ($result as &$r) {
                unset($r['thumbnailphoto']);
            }
            echo '<pre>';
            print_r($result);
            die();
        }

        return $result;
    }

    public function getAllDisabled()
    {
        //wywlam na czas odbierania $this->zmianyDoWypchniecia = $this->container->get('doctrine')->getManager()->getRepository('ParpMainBundle:Entry')->findByIsImplemented(0, ['samaccountname' => 'ASC', 'id' => 'ASC']);
        $userdn = $this->useradn . $this->patch;

        //$userdn = str_replace("OU=Zespoly_2016, OU=PARP Pracownicy ,", "", str_replace("OU=Zespoly_2016,OU=PARP Pracownicy ,", "", $userdn));


        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $letters_array = str_split($letters);

        $tmpResults = array();
        foreach ($letters_array as $letter) {
            $search = ldap_search($ldapconn, $userdn, '(&(samaccountname=' . $letter .
                    '*)(objectClass=person)(|(userAccountControl=514)(userAccountControl=66050)))', $this->ADattributes);
            $results = ldap_get_entries($ldapconn, $search);
            $tmpResults = array_merge($tmpResults, $results);
        }
        ldap_bind($ldapconn);


        $result = $this->parseResults($tmpResults);
        $justDump = false;
        if ($justDump) {
            foreach ($result as &$r) {
                unset($r['thumbnailphoto']);
            }
            echo '<pre>';
            print_r($result);
            die();
        }

        return $result;
    }

    public function getMembersOfGroupFromAD($group = false, $inclusive = false)
    {

        $group = $group ? ldap_escape($group) : $group;
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = 'OU=' . $this->_ouWithGroups . $this->patch;
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        // Begin building query
        if ($group) {
            $query = '(&';
        } else {
            $query = '';
        }

        $query .= '(&(objectClass=person))';

        // Filter by memberOf, if group is set
        if (is_array($group)) {
            // Looking for a members amongst multiple groups
            if ($inclusive) {
                // Inclusive - get users that are in any of the groups
                // Add OR operator
                $query .= '(|';
            } else {
                // Exclusive - only get users that are in all of the groups
                // Add AND operator
                $query .= '(&';
            }

            // Append each group
            foreach ($group as $g) {
                $query .= "(memberOf=CN=$g,$ldap_dn_grupy)";
            }

            $query .= ')';
        } elseif ($group) {
            // Just looking for membership of one group
            $query .= "(memberOf=CN=$group,$ldap_dn_grupy)";
        }

        // Close query
        if ($group) {
            $query .= ')';
        } else {
            $query .= '';
        }


        try {
            $search = ldap_search($ldapconn, $userdn, $query, $this->ADattributes);
        } catch (\Exception $e) {
            die("Blad wyszukiwania w AD, szukano <br>'" . $query . "' <br><br>" . $e->getMessage() . ' ');
        }


        $results = ldap_get_entries($ldapconn, $search);
        ldap_bind($ldapconn);

        $result = $this->parseResults($results);

        return $result;
    }

    public function getOUsFromAD($ou)
    {

        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $userdn = $this->useradn . $this->patch;
        $userdn = str_replace('OU=Zespoly_2016,', '', $userdn);


        $filter = '(objectClass=organizationalunit)';
        $justthese = array(
            'objectclass',
            'ou',
            'distinguishedname',
            'instancetype',
            'whencreated',
            'whenchanged',
            'usncreated',
            'usnchanged',
            'name',
            //"objectguid",
            'objectcategory',
            //"gplink",
            'dscorepropagationdata',
            'dn',
        );


        $sr = ldap_search($ldapconn, $userdn, $filter, $justthese);
        $info = ldap_get_entries($ldapconn, $sr);

        ldap_free_result($sr);
        ldap_unbind($ldapconn);

        //print_r($info); die();
        return $info;
    }

    public function getGroupsFromAD($group, $wilcardSearch = '')
    {
        //!!!!!!to nie dziala , uzywam do grup bundle AdLdap
        // Begin building query
        $query = '(&';
        $query .= '(&(objectClass=group))';
        $group2 = ldap_escape($group);
        if ($group) {
            $query .= "(CN=$group2$wilcardSearch)";
        } else {
            $query .= '(CN=*)';
        }


        // Close query
        $query .= ')';

        $ret = $this->paginatedSearch($query);

        return $ret;
        /*
          $letters = "abcdefghijklmnopqrstuvwxyz1234567890-_";
          $letters_array = str_split($letters);

          $tmpResults = array();
          if($group){

          }else{
          //jesli bierzemy wszystko to w iteracji po kloei literkami alfabetu bo wystepuja blad:
          //Warning: ldap_search(): Partial search results returned: Sizelimit exceeded
          foreach ($letters_array as $letter) {
          foreach ($letters_array as $letter2) {
          foreach ($letters_array as $letter3) {
          echo ".szukam literki $letter$letter2$letter3 ...";
          $results = $this->getGroupsFromADint($letter.$letter2.$letter3, "*");
          $tmpResults = array_merge($tmpResults, $results);
          }
          }
          }
          }

          return $tmpResults;
         */
    }

    private function paginatedSearch($filter, $pageSize = 500)
    {
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = 'OU=' . $this->_ouWithGroups . $this->patch;
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;

        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 20000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');
        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        $cookie = '';
        $result = [];
        $result['count'] = 0;
        do {
            ldap_control_paged_result($ldapconn, $pageSize, true, $cookie);
            //var_dump($ldapconn, $ldap_dn_grupy, $filter);
            $sr = ldap_search($ldapconn, $ldap_dn_grupy, $filter);
            $entries = ldap_get_entries($ldapconn, $sr);
            $entries['count'] += $result['count'];

            $result = array_merge($result, $entries);

            ldap_control_paged_result_response($ldapconn, $sr, $cookie);
        } while ($cookie !== null && $cookie != '');

        return $result;
    }

    public function getGroupsFromADint($group, $wilcardSearch = '')
    {
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = 'OU=' . $this->_ouWithGroups . $this->patch;
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 20000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        // Begin building query
        $query = '(&';
        $query .= '(&(objectClass=group))';
        $group2 = ldap_escape($group);
        if ($group) {
            $query .= "(CN=$group2$wilcardSearch)";
        }


        // Close query
        $query .= ')';
        try {
            $search = ldap_search($ldapconn, $ldap_dn_grupy, $query);
        } catch (\Exception $e) {
            die("Blad wyszukiwania w AD, szukano <br>'" . $query . "' <br><br>" . $e->getMessage() . ' ');
        }

        $results = ldap_get_entries($ldapconn, $search);
        ldap_bind($ldapconn);

        return $results;
    }

    public function getAllUserGroupsRecursivlyFromAD($samaccountname)
    {
        $user = $this->getUserFromAD($samaccountname);
        //echo "<pre>"; print_r($user); die();
        $items = [];
        if (count($user) > 0) {
            $ldap_username = $this->container->getParameter('ad_user');
            $ldap_password = $this->container->getParameter('ad_password');
            $ldapdomain = $this->ad_domain;
            $userDN = ldap_escape($user[0]['distinguishedname']);
            $searchDN = 'OU=' . $this->_ouWithGroups . $this->patch; //"DC=parp,DC=local";
            $ldapconn = ldap_connect($this->ad_host);
            ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
            ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
            $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
            $groupToFind = 'SG-ZZP-Public-RO'; //"INT_BI";
            $filter = '(memberof:1.2.840.113556.1.4.1941:=' . $groupToFind . ')';
            //$filter = "(samaccountname=".$groupToFind.")";
            $filter = '(&(objectclass=*)(member:1.2.840.113556.1.4.1941:=' . $userDN . '))';
            $search = ldap_search($ldapconn, $searchDN, $filter, array('dn'), 1);
            $items = ldap_get_entries($ldapconn, $search);
            //echo "<pre>"; print_r($items);print_r($userDN); die();
        }

        return $items;
    }

    public function getAllUserGroupsRecursivlyFromADbeasedOnGroup($groupToFind)
    {
        //echo "<pre>"; print_r($user); die();
        $items = [];
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');
        $ldapdomain = $this->ad_domain;
        $searchDN = 'OU=' . $this->_ouWithGroups . $this->patch; //"DC=parp,DC=local";
        $ldapconn = ldap_connect($this->ad_host);
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        //$groupToFind = "SG-ZZP-Public-RO";//"INT_BI";
        //$filter = "(memberof:1.2.840.113556.1.4.1941:=".$groupToFind.")";
        //$filter = "(samaccountname=".$groupToFind.")";
        $filter = '(&(objectclass=*)(member:1.2.840.113556.1.4.1941:=' . $groupToFind . '))';
        $search = ldap_search($ldapconn, $searchDN, $filter, array('dn'), 1);
        $items = ldap_get_entries($ldapconn, $search);

        //echo "<pre>"; print_r($items);print_r($userDN); die();

        return $items;
    }

    public function checkGroupExistsFromAD($group)
    {
        $result = $this->getGroupsFromAD($group);


        return ($result['count']) > 0;
    }

    public function getAccountControl($userAccountControl)
    {

        $binary = decbin($userAccountControl);

        $binArray = array_reverse(str_split($binary));
        $accountControl = '';

        if (isset($binArray['0'])) {
            if ($binArray['0'] == 1) {
                $accountControl .= 'SCRIPT,';
            }
        }
        if (isset($binArray['1'])) {
            if ($binArray['1'] == 1) {
                $accountControl .= 'ACCOUNTDISABLE,';
            }
        }
        if (isset($binArray['2'])) {
            if ($binArray['2'] == 1) {
                $accountControl .= 'HOMEDIR_REQUIRED,';
            }
        }
        if (isset($binArray['3'])) {
            if ($binArray['3'] == 1) {
                $accountControl .= 'LOCKOUT,';
            }
        }
        if (isset($binArray['4'])) {
            if ($binArray['4'] == 1) {
                $accountControl .= 'PASSWD_NOTREQD,';
            }
        }
        if (isset($binArray['5'])) {
            if ($binArray['5'] == 1) {
                $accountControl .= 'PASSWD_CANT_CHANGE,';
            }
        }
        if (isset($binArray['6'])) {
            if ($binArray['6'] == 1) {
                $accountControl .= 'ENCRYPTED_TEXT_PWD_ALLOWED,';
            }
        }
        if (isset($binArray['7'])) {
            if ($binArray['7'] == 1) {
                $accountControl .= 'TEMP_DUPLICATE_ACCOUNT,';
            }
        }
        if (isset($binArray['8'])) {
            if ($binArray['8'] == 1) {
                $accountControl .= 'NORMAL_ACCOUNT,';
            }
        }
        if (isset($binArray['9'])) {
            if ($binArray['9'] == 1) {
                $accountControl .= 'INTERDOMAIN_TRUST_ACCOUNT,';
            }
        }
        if (isset($binArray['10'])) {
            if ($binArray['10'] == 1) {
                $accountControl .= 'WORKSTATION_TRUST_ACCOUNT,';
            }
        }
        if (isset($binArray['11'])) {
            if ($binArray['11'] == 1) {
                $accountControl .= 'SERVER_TRUST_ACCOUNT,';
            }
        }
        if (isset($binArray['12'])) {
            if ($binArray['12'] == 1) {
                $accountControl .= 'DONT_EXPIRE_PASSWORD,';
            }
        }
        if (isset($binArray['13'])) {
            if ($binArray['13'] == 1) {
                $accountControl .= 'MNS_LOGON_ACCOUNT,';
            }
        }
        if (isset($binArray['14'])) {
            if ($binArray['14'] == 1) {
                $accountControl .= 'SMARTCARD_REQUIRED,';
            }
        }
        if (isset($binArray['15'])) {
            if ($binArray['15'] == 1) {
                $accountControl .= 'TRUSTED_FOR_DELEGATION,';
            }
        }
        if (isset($binArray['16'])) {
            if ($binArray['16'] == 1) {
                $accountControl .= 'NOT_DELEGATED,';
            }
        }
        if (isset($binArray['17'])) {
            if ($binArray['17'] == 1) {
                $accountControl .= 'USE_DES_KEY_ONLY,';
            }
        }
        if (isset($binArray['18'])) {
            if ($binArray['18'] == 1) {
                $accountControl .= 'DONT_REQ_PREAUTH,';
            }
        }
        if (isset($binArray['19'])) {
            if ($binArray['19'] == 1) {
                $accountControl .= 'PASSWORD_EXPIRED,';
            }
        }
        if (isset($binArray['20'])) {
            if ($binArray['20'] == 1) {
                $accountControl .= 'TRUSTED_TO_AUTH_FOR_DELEGATION,';
            }
        }
        if (isset($binArray['21'])) {
            if ($binArray['21'] == 1) {
                $accountControl .= 'PARTIAL_SECRETS_ACCOUNT,';
            }
        }

        return $accountControl;
    }

    /**
     * @param string|null   $samaccountname
     * @param string|null   $cnname
     * @param string|null   $query
     * @param string $ktorych
     *
     * @return array
     */
    public function getUserFromAD($samaccountname = null, $cnname = null, $query = null, $ktorych = 'aktywni')
    {
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        if ($ktorych === 'aktywni') {
            //nic nie zmieniamy
        } elseif ($ktorych === 'zablokowane') {
            $userdn = str_replace('OU=Zespoly_2016,', 'OU=Zablokowane,', $userdn);
        } elseif ($ktorych === 'wszyscyWszyscy') {
            $userdn = str_replace(
                'OU=Zespoly_2016, OU=PARP Pracownicy ,',
                '',
                str_replace(
                    'OU=Zespoly_2016,OU=PARP Pracownicy ,',
                    '',
                    $userdn
                )
            );
            $userdn = str_replace('OU=Zespoly_2016,', '', $userdn);
        } elseif ($ktorych === 'nieobecni') {
            $userdn = str_replace('OU=Zespoly_2016,', 'OU=Nieobecni,', $userdn);
        }

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) || die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        if ($samaccountname) {
            $searchString = '(&(samaccountname=' . $samaccountname . ')(objectClass=person))';
        } elseif ($cnname) {
            if (false !== strpos($cnname, '*')) {
                $ps = explode('*', $cnname);
                $ret = [];
                foreach ($ps as $p) {
                    $ret[] = ldap_escape($p);
                }
                $cnname = implode('*', $ret);
            } else {
                $cnname = ldap_escape($cnname);
            }
            $searchString = '(&(name=*' . $cnname . '*)(objectClass=person))';
        } elseif ($query) {
            $searchString = '(&(' . $query . ')(objectClass=person))';
        } else {
            $searchString = '(objectClass=person)';
        }

        $search = ldap_search($ldapconn, $userdn, $searchString, $this->ADattributes);
        $tmpResults = ldap_get_entries($ldapconn, $search);
        ldap_unbind($ldapconn);

        $result = $this->parseResults($tmpResults);
        if (count($result) > 0) {
            /* wylaczam na czas odbierania uprawnien, bo zamula
              //dodaje zmiany do wypchniecia
              $this->zmianyDoWypchniecia = $this->container->get('doctrine')->getManager()->getRepository('ParpMainBundle:Entry')->findBy(
              ['isImplemented' => 0, 'samaccountname' => $result[0]['samaccountname']], ['samaccountname' => 'ASC', 'id' => 'ASC']
              );

              $zmiany = [];
              foreach($this->zmianyDoWypchniecia as $z){
              $zmiany[$z->getSamaccountname()][] = $z;
              }
              $u = &$result[0];
              if(isset($zmiany[$u['samaccountname']])){
              //mamy zmiany
              $noweAttr = $this->parseZmianyUsera($u, $zmiany);
              foreach($noweAttr as $k => $v){
              $u[$k."inAD"] = $u[$k];
              $u[$k] = $v;
              }
              }
             */
        }

        return $result;
    }

    public function getNieobecnyUserFromAD($samaccountname = null, $cnname = null, $query = null)
    {
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        $userdn = str_replace('OU=Zespoly,', '', $userdn);
        //die($userdn);
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        if ($samaccountname) {
            $searchString = '(&(samaccountname=' . $samaccountname . ')(objectClass=person))';
        } elseif ($cnname) {
            $cnname = ldap_escape($cnname);
            $searchString = '(&(name=*' . $cnname . '*)(objectClass=person))';
        } elseif ($query) {
            $searchString = '(&(' . $query . ')(objectClass=person))';
        } else {
            $searchString = '(objectClass=person)';
        }


        $search = ldap_search($ldapconn, $userdn, $searchString, $this->ADattributes);
        $tmpResults = ldap_get_entries($ldapconn, $search);
        ldap_unbind($ldapconn);

        $result = $this->parseResults($tmpResults);

        return $result;
    }

    protected function parseResults($tmpResults)
    {
        $result = array();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if (is_array($tmpResult) && isset($tmpResult['samaccountname'])) {
                $date = new \DateTime();
                $time = $this->LDAPtoUnix($tmpResult['accountexpires'][0]);
                $date->setTimestamp($time);
                $result[$i]['isDisabled'] = $tmpResult['useraccountcontrol'][0] == '546' ? 1 : 0;
                $result[$i]['samaccountname'] = $tmpResult['samaccountname'][0];
                $result[$i]['accountExpires'] = $date->format('Y') > 2000 && $date->format('Y') < 3000 ? $date->format('Y-m-d') : '';
                if (isset($tmpResult['accountexpires'][0])) {
                    if (
                        $tmpResult['accountexpires'][0] == 9223372036854775807 ||
                            $tmpResult['accountexpires'][0] == 0
                    ) {
                        $result[$i]['accountexpires'] = '';
                    } else {
                        $result[$i]['accountexpires'] = date('Y-m-d H:i:s', $tmpResult['accountexpires'][0] / 10000000 - 11644473600);
                    }
                }

                $result[$i]['name'] = isset($tmpResult['name'][0]) ? $tmpResult['name'][0] : '';
                $result[$i]['email'] = isset($tmpResult['mail'][0]) ? $tmpResult['mail'][0] : '';
                $result[$i]['mailnickname'] = isset($tmpResult['mailnickname'][0]) ? $tmpResult['mailnickname'][0] : '';
                $result[$i]['initials'] = isset($tmpResult['initials'][0]) ? $tmpResult['initials'][0] : '';
                $result[$i]['title'] = isset($tmpResult['title'][0]) ? $tmpResult['title'][0] : '';
                $result[$i]['info'] = isset($tmpResult['info'][0]) ? $tmpResult['info'][0] : '';
                $result[$i]['department'] = isset($tmpResult['department'][0]) ? $tmpResult['department'][0] : '';
                $result[$i]['description'] = isset($tmpResult['description'][0]) ? $tmpResult['description'][0] : '';
                $result[$i]['division'] = isset($tmpResult['division'][0]) ? $tmpResult['division'][0] : '';
                //$result[$i]["disableDescription"] = str_replace("Konto wyłączone bo: ", "", $tmpResult["description"][0]);
                $result[$i]['lastlogon'] = isset($tmpResult['lastlogon']) ? date(
                    'Y-m-d H:i:s',
                    $tmpResult['lastlogon'][0] / 10000000 - 11644473600
                ) : '';
                //$result[$i]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
                $result[$i]['manager'] = isset($tmpResult['manager'][0]) ? $tmpResult['manager'][0] : '';
                $result[$i]['thumbnailphoto'] = isset($tmpResult['thumbnailphoto'][0]) ? $tmpResult['thumbnailphoto'][0] : '';
                $result[$i]['useraccountcontrol'] = isset($tmpResult['useraccountcontrol'][0]) ? $this->getAccountControl($tmpResult['useraccountcontrol'][0]) : '';
                $result[$i]['distinguishedname'] = $tmpResult['distinguishedname'][0];
                $result[$i]['cn'] = $tmpResult['cn'][0];
                $result[$i]['memberOf'] = $this->parseMemberOf($tmpResult);
                $result[$i]['extensionAttribute10'] =  isset($tmpResult['extensionattribute10'][0]) ? $tmpResult['extensionattribute10'][0] : '';

                if ('ekranEdycji' === $this->dodatkoweOpcje) {
                    $roles = $this->container->get('doctrine')
                            ->getRepository('ParpMainBundle:AclUserRole')
                            ->findBy([
                        'samaccountname' => $tmpResult['samaccountname'][0]
                            ]);

                    $rs = array();
                    foreach ($roles as $role) {
                        $rs[] = $role->getRole()->getName();
                    }
                    $result[$i]['roles'] = $rs;
                }

                $i++;
            }
        }

        usort($result, function ($item1, $item2) {
            return $item1['name'] >= $item2['name'];
        });

        return $result;
    }

    protected function parseMemberOf($res)
    {

        $ret = array();
        $gr = isset($res['memberof']) ? $res['memberof'] : array();
        foreach ($gr as $k => $g) {
            if ($k !== 'count') {
                $p = explode(',', $g);
                $p2 = str_replace('CN=', '', $p[0]);
                $ret[] = $p2;
            }
        }

        return $ret;
    }

    public function getUsersFromOU($OU)
    {
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $userdn = 'OU=' . $OU . ',' . $this->useradn . $this->patch;
        $result = null;
        try {
            $search = ldap_search($ldapconn, $userdn, '(&(samaccountname=*)(objectClass=person))', array(
                'samaccountname',
                'name',
                'initials',
            ));
            $tmpResults = ldap_get_entries($ldapconn, $search);
            ldap_unbind($ldapconn);

            $result = array();
            $i = 0;
            foreach ($tmpResults as $tmpResult) {
                if ($tmpResult['samaccountname']) {
                    $result[$i]['samaccountname'] = $tmpResult['samaccountname'][0];
                    $result[$i]['name'] = isset($tmpResult['name'][0]) ? $tmpResult['name'][0] : '';
                    $result[$i]['initials'] = isset($tmpResult['initials'][0]) ? $tmpResult['initials'][0] : '';
                    $i++;
                }
            }
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * Zwraca tablicę pracowników podanego D/B z AD
     *
     * @param string $skrotDb
     *
     * @throws Exception
     *
     * @return array
     */
    public function getPracownicyDepartamentu($skrotDb): array
    {
        if (!preg_match("/^[A-Z]{2,3}$/", $skrotDb)) {
            throw new Exception(sprintf('%s: Błędny D/B: "%s"', __FUNCTION__, $skrotDb));
        }

        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn) {
            throw new Exception('Brak połączenia z serwerem domeny!');
        }
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $userdn = 'OU=' . $skrotDb . ',' . $this->useradn . $this->patch;

        $result = [];

        try {
            $search = ldap_search(
                $ldapconn,
                $userdn,
                '(&(samaccountname=*)(objectClass=person)(!(name=*Testowy))(!(samaccountname=testowe_*)))',
                $this->ADattributes
            );
            $tmpResults = ldap_get_entries($ldapconn, $search);
            ldap_unbind($ldapconn);
            $result = $this->parseResults($tmpResults);
        } catch (Exception $e) { }

        return $result;
    }

    protected function LDAPtoUnix($ldap_ts)
    {
        return ($ldap_ts / 10000000) - 11644473600;
    }

    protected function unixToLdap($unix_ts)
    {
        return sprintf('%.0f', ($unix_ts + 11644473600) * 10000000);
    }

    public function getGrupa($grupa)
    {
        return $this
            ->ldapFetch
            ->fetchGroup($grupa)
        ;
    }

    public function getUsersWithRole($role)
    {
        if ($this->_userCache === null || $this->dodatkoweOpcje == '') {
            $popOpcje = $this->dodatkoweOpcje;
            $this->dodatkoweOpcje = 'ekranEdycji';
            $this->_userCache = $this->getAllFromAD();
            $this->dodatkoweOpcje = $popOpcje;
        }
        //echo "<pre>"; print_r($this->_userCache); die();
        $ret = [];
        foreach ($this->_userCache as $u) {
            if (in_array($role, $u['roles'], true)) {
                $ret[$u['samaccountname']] = $u['name'];
            }
        }

        return $ret;
    }

    public function getWlascicieleZasobow()
    {
        return $this->getUsersWithRole('PARP_WLASCICIEL_ZASOBOW');
    }

    public function getAdministratorzyZasobow()
    {
        return $this->getUsersWithRole('PARP_ADMIN_ZASOBOW');
    }

    public function getAdministratorzyTechniczniZasobow()
    {
        return $this->getUsersWithRole('PARP_ADMIN_TECHNICZNY_ZASOBOW');
    }

    public function getDyrektorow()
    {
        //$users = $this->getAllFromAD();
        $deps = $this->container->get('doctrine')
                ->getManager()
                ->getRepository(Departament::class)
                ->findByNowaStruktura(1);
        $ret = [];
        foreach ($deps as $d) {
            $dyr = $this->getUserFromAD($d->getDyrektor());
            if (count($dyr) > 0) {
                $ret[] = $dyr[0];
            }
        }

        /*
          $ret = [];
          foreach($users as $u){
          if(
          mb_strtolower(trim($u['title'])) == "dyrektor" ||
          mb_strtolower(trim($u['title'])) == "p.o. dyrektora" ||
          mb_strtolower(trim($u['title'])) == "po dyrektora"

          ){
          unset($u['thumbnailphoto']);
          unset($u['memberOf']);
          unset($u['roles']);
          $ret[] = $u;
          }
          }
         */

        return $ret;
    }

    public function getPrezes()
    {
        /*
          $users = $this->getAllFromAD();
          $ret = [];
          foreach($users as $u){
          if(mb_strtolower(trim($u['title'])) == "prezes"){
          $ret = $u;
          }
          }
         */
        $user = $this->getUserFromAD('jadwiga_lesisz');
        $ret = $user[0];

        return $ret;
    }

    public function getDyrektoraDepartamentu($skrot)
    {
        $dyrs = $this->getDyrektorow();

        foreach ($dyrs as $d) {
            if (mb_strtoupper(trim($d['description'])) == mb_strtoupper(trim($skrot))) {
                return $d;
            }
        }

        throw new EntityNotFoundException('Nie znaleziono dyrektora departamentu o nazwie ' . $skrot);
    }

    public function kogoBracJakoManageraDlaUseraDoWniosku($user)
    {
        switch (mb_strtolower(trim($user['title']))) {
            case 'rzecznik beneficjenta parp, dyrektor':
            case 'dyrektor':
            case 'p.o. dyrektora':
            case 'dyrektor (p.o.)':
            case 'główny księgowy,dyrektor':
            case 'główny księgowy, dyrektor':
            case 'główny księgowy':
            case 'dyrektor, Rzecznik Funduszy Europejskich PARP':
                $ret = 'manager';
                break;
            case 'zastępca prezesa':
            case 'zastępca prezesa (p.o.)':
            case 'prezes':
            case 'p.o. prezesa':
                $ret = 'prezes';
                break;
            default:
                $ret = 'dyrektor';
                break;
        }

        return $ret;
    }

    protected $stanowiskaDyrektorzy = [
        'dyrektor',
        'p.o. dyrektora',
        'główny księgowy',
    ];

    protected $stanowiskaWiceDyrektorzy = [
        'zastępca dyrektora',
        'p.o. zastępcy dyrektora',
    ];

    public function getSekcjePodwladnych($manager)
    {
        $users = $this->getAllFromAD();
        $podwladni = [];
        foreach ($users as $user) {
            if (isset($manager['distinguishedname']) && $user['manager'] === $manager['distinguishedname']) {
                $dodac = $user['division'];
                if (!in_array($dodac, $podwladni, true)) {
                    $podwladni[] = $dodac;
                }
                $stanowisko = mb_strtolower(trim($user['title']));
                if (in_array($stanowisko, $this->stanowiskaWiceDyrektorzy, true)) {
                    $podwladniWiceDyrektora = $this->getSekcjePodwladnych($user);
                    $podwladni = array_merge($podwladni, $podwladniWiceDyrektora);
                }
            }
        }

        return $podwladni;
    }

    /**
     * Zwraca grupy AD z UPP na podstawie stanowiska, D/B, skrótu sekcji i roli w AkD
     * (na podstaiwe rejestru uprawnień początkowych v1.6)
     *
     * @param array  $user         użytkownik z AD
     * @param string $depshortname skrót D/B
     * @param string $sekcja       skrót sekcji
     *
     * @return array
     *
     * @throws Exception
     */
    public function getGrupyUsera($user, $depshortname, $sekcja)
    {
        $stanowisko = mb_strtolower(trim($user['title']));
        $entityManager = $this->container->get('doctrine')->getManager();

        if (!$depshortname instanceof Departament) {
            $departament = $entityManager->getRepository(Departament::class)->findOneBy([
                'shortname' => $depshortname,
                'nowaStruktura' => 1,
            ]);
            $depshortname = $departament;
        } else {
            $departament = $depshortname;
        }

        $pomijajSekcje = ['ND', 'BRAK', 'N/D', 'n/d', ''];
        // [UPP]
        $grupy = [
            'Pracownicy',
            'DLP-gg-USB_CD_DVD-DENY',
            'SGG-(skrót D/B)-Wewn-Wsp-RW'
        ];

        if ($sekcja instanceof Section) {
            $sekcja = $sekcja->getName();
        }

        // dostęp do własnego katalogu sekcyjnego
        if (!empty($sekcja) && !in_array($sekcja, $pomijajSekcje, true)) {
            // poniższy warunek do usuniecia, jeśli zostaną wszędzie przerobione odwołania do getGrupyUsera z nazw sekcji na skróty.
            if (strstr($sekcja, '.') === false) {
                if (!$sekcja instanceof Section) {
                    $section = $entityManager->getRepository(Section::class)->findOneBy([
                        'departament' => $departament->getId(),
                        'name' => $sekcja
                    ]);
                } else {
                    $section = $sekcja;
                }
                if (null === $section) {
                    throw new Exception('Nie znaleziono sekcji: ' . $sekcja . ' w departamencie: ' . $depshortname . ' (pracownik: ' . $user['name'] . ')'
                        . ' #class:' . debug_backtrace()[1]['class'] . ' #function:' . debug_backtrace()[1]['function']);
                }
                $sekcja = $section->getShortname();
            }
            $skrotSekcjiRozbity = explode('.', $sekcja);

            if (strtoupper($skrotSekcjiRozbity[0]) != strtoupper($depshortname->getShortname())) {
                throw new Exception('Niewłaściwy D/B (' . $depshortname->getShortname() . ') lub sekcja (' . $sekcja . ') dla pracownika: ' . $user['name']
                    . ' #class:' . debug_backtrace()[1]['class'] . '#function:' . debug_backtrace()[1]['function']);
            }
            if ($skrotSekcjiRozbity[1][0] === 'S') {
                $grupy[] = 'SGG-(skrót D/B)-Wewn-(skrót sekcji)-RW';
            }
        }

        // uprawnienia na podstawie stanowiska
        switch ($stanowisko) {
            // [UPr], [UZPr]
            case 'prezes':
            case 'p.o. prezesa':
            case 'zastępca prezesa':
            case 'zastępca prezesa (p.o.)':
                $grupy[] = 'INT Olimp';
                $grupy[] = 'INT-Prezesi';
                break;
            // [UD]
            case 'dyrektor':
            case 'p.o. dyrektora':
            case 'główny księgowy':
                $grupy[] = 'INT Olimp';
                $grupy[] = 'INT-Dyrektorzy';
                $grupy[] = 'SG-Olimp-RW';
                $grupy[] = 'SGG-(skrót D/B)-Olimp-RW';
                $grupy[] = 'SGG-(skrót D/B)-Public-RW';
                break;
            // [UZD]
            case 'zastępca dyrektora':
            case 'p.o. zastępcy dyrektora':
                $grupy[] = 'INT Olimp';
                $grupy[] = 'INT-Dyrektorzy';
                $grupy[] = 'INT-Zastepcy-Dyrektorow';
                $grupy[] = 'SG-Olimp-RW';
                $grupy[] = 'SGG-(skrót D/B)-Olimp-RW';
                $grupy[] = 'SGG-(skrót D/B)-Public-RW';
                break;
            // [UK]
            case 'kierownik':
            case 'p.o. kierownika':
                $grupy[] = 'INT-Kierownicy';
                break;
        }

        for ($i = 0; $i < count($grupy); $i++) {
            $grupy[$i] = str_replace('(skrót sekcji)', $sekcja, str_replace('(skrót D/B)', $depshortname->getShortname(), $grupy[$i]));
        }

        if (null !== $depshortname && !empty($depshortname->getGrupyAD())) {
            $grupyDepartamentowe = explode(';', $depshortname->getGrupyAD());
            foreach ($grupyDepartamentowe as $grupaDep) {
                if ($grupaDep != '') {
                    $grupy[] = $grupaDep;
                }
            }
        }

        if (in_array($stanowisko, $this->stanowiskaDyrektorzy, true)) {
            //przeleciec rekurencyjnie wszystkich podwladnych
            $sekcje = $this->getSekcjePodwladnych($user);
            foreach ($sekcje as $sekcja) {
                if ($sekcja != '' && !in_array($sekcja, $pomijajSekcje, true)) {
                    if (explode('.', $sekcja)[1][0] === 'S') {
                        $grupaDoDodania = 'SGG-' . $depshortname->getShortname() . '-Wewn-' . $sekcja . '-RW';
                        if (!in_array($grupaDoDodania, $grupy, true)) {
                            $grupy[] = $grupaDoDodania;
                        }
                    }
                }
            }
        }

        return $grupy;
    }

    public function getOUfromDN($u)
    {
        $cz = explode(',', $u['distinguishedname']);
        $ou = str_replace('OU=', '', $cz[1]);

        return $ou;
    }

    private $stanowiska = [
        'prezes',
        'p.o. prezesa',
        'zastępca prezesa',
        'dyrektor',
        'p.o. dyrektora',
        'zastępca dyrektora',
        'p.o. zastępcy dyrektora',
        'kierownik sekcji',
        'p.o. kierownika sekcji',
        'kierownik',
        'p.o. kierownika',
        'koordynator projektu',
        'główny księgowy, dyrektor',
        'główny księgowy',
        'główny księgowy, z-ca dyrektora',
        'główny księgowy, zastępca dyrektora',
        'główny księgowy - p.o. zastępcy dyrektora',
        'rzecznik beneficjenta parp, dyrektor',
        'rzecznik beneficjenta parp',
        'zastępca dyrektora, radca prawny',
        'rzecznik prasowy, zastępca dyrektora',
    ];

    public function getPrzelozeni()
    {
        //echo "<pre>"; print_r($this->_userCache);
        if ($this->_userCache === null) {
            $this->_userCache = $this->getAllFromAD();
        }

        $ret = null;
        foreach ($this->_userCache as $u) {
//            if (in_array(trim(strtolower($u['title'])), $this->stanowiska)) {
            $ret[$u['samaccountname']] = $u;
//            }
        }

        return $ret;
    }

    /**
     * Zwraca przełożonego danego użytkownika
     *
     * @param $samaccountname
     * @return mixed|string
     * @throws EntityNotFoundException
     */
    public function getPrzelozony($samaccountname)
    {
        $ADUser = $this->getUserFromAD($samaccountname);

        if (!isset($ADUser[0]['manager'])) {
            throw new EntityNotFoundException('Nie znaleziono przełożonego dla użytkownika ' . $samaccountname);
        }

        $mancn = str_replace('CN=', '', substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));
        $ADManager = $this->getUserFromAD(null, $mancn);

        return isset($ADManager[0]) ? $ADManager[0] : '';
    }

    /**
     * Zwraca przełożonego danego użytkownika jako tablica
     *
     * @param $samaccountname
     * @return array
     * @throws EntityNotFoundException
     */
    public function getPrzelozonyJakoTablica($samaccountname)
    {
        $ADUser = $this->getUserFromAD($samaccountname);

        if (!isset($ADUser[0]['manager'])) {
            throw new EntityNotFoundException('Nie znaleziono przełożonego dla danego użytkownika');
        }

        $mancn = str_replace('CN=', '', substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));
        $ADManager = $this->getUserFromAD(null, $mancn);

        return $ADManager;
    }

    public function getPrzelozeniJakoName($tablicaZLoginem = false)
    {
        if ($this->_userCache === null) {
            $this->_userCache = $this->getAllFromAD();
        }
        //echo "<pre>"; print_r($this->_userCache); die();
        $ret = [];
        foreach ($this->_userCache as $u) {
            if (in_array(trim($u['title']), $this->stanowiska, true)) {
                if ($tablicaZLoginem) {
                    $ret[$u['name']] = $u['samaccountname'];
                } else {
                    $ret[$u['name']] = $u['name'];
                }
            }
        }

        return $ret;
    }

    public function getZarzad()
    {
        $ludzie = $this->getAllFromAD();
        $ret = [];
        $stanowiska = ['zastępca dyrektora', 'p.o. dyrektora', 'dyrektor', 'prezes', 'p.o. prezesa', 'zastępca prezesa'];
        foreach ($ludzie as $u) {
            if (in_array($u['title'], $stanowiska, true)) {
                $ret[] = $u;
            }
        }

        return $ret;
    }

    /**
     * @param string $dodatkoweOpcje
     */
    public function setDodatkoweOpcje($dodatkoweOpcje)
    {
        $this->dodatkoweOpcje = $dodatkoweOpcje;
    }

    /**
     * @return string
     */
    public function getDodatkoweOpcje()
    {
        return $this->dodatkoweOpcje;
    }

    /**
     * Funkcja zwraca przełozonego pracownika
     * Dla "zwykłych" pracowników znajduje dyrektora, od dyrektora
     * zwraca bezpośredniego przełożonego
     *
     * @param string $samaccountname
     * @return array
     */
    public function getPrzelozonyPracownika($samaccountname)
    {
        $pracownik = $this->getUserFromAD($samaccountname)[0];

        $zarzad = $stanowiska = ['zastępca dyrektora', 'p.o. dyrektora', 'dyrektor', 'prezes', 'p.o. prezesa', 'zastępca prezesa'];

        if (in_array($pracownik['title'], $zarzad)) {
            return $pracownik;
        }
        $przelozony = $this->getPrzelozony($samaccountname);

        return $this->getPrzelozonyPracownika($przelozony['samaccountname']);
    }

    /**
     * Zresetowanie ldap.cache
     *
     * @return void
     */
    public function clearLdapCache()
    {
        $cache = $this->cache;
        $cache->clear();
    }
}
