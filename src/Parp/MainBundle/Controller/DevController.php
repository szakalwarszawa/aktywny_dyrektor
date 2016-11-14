<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Form\EngagementType;
use Parp\MainBundle\Form\UserEngagementType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Action\MassAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Parp\MainBundle\Entity\UserZasoby;
use Parp\MainBundle\Form\UserZasobyType;
use Parp\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Parp\MainBundle\Entity\HistoriaWersji;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Zasoby controller.
 *
 * @Route("/dev")
 */
class DevController extends Controller
{
    /**
     * @Route("/testAdLib3", name="testAdLib3")
     * @Template()
     */
    public function testAdLib3Action()
    {
        $samaccountname = "zbigniew_organisciak";
        $ldap = $this->get('ldap_service');
        
        
        $ADManager = $ldap->getUserFromAD(null, "Magdalena Warecka");
        if(count($ADManager) > 0) echo "Magdalena Warecka jest ";
        $ADManager = $ldap->getUserFromAD(null, "Warecka Magdalena");
        if(count($ADManager) > 0) echo "Warecka Magdalena jest ";
        
        
        $ADManager = $ldap->getUserFromAD(null, "Marszałek Artur");
        if(count($ADManager) > 0) echo "Marszałek Artur jest ";
        $ADManager = $ldap->getUserFromAD(null, "Artur Marszałek");
        if(count($ADManager) > 0) echo "Artur Marszałek jest ";
        //print_r($ADManager);
        die();
        
        
        $userGroups = $ldap->getAllUserGroupsRecursivlyFromAD($samaccountname);
        echo "<pre>"; print_r($userGroups); die();
        die();
        
    }
    /**
     * @Route("/testAdLib2", name="testAdLib2")
     * @Template()
     */
    public function testAdLib2Action()
    {
        $configuration = array(
            //'user_id_key' => 'samaccountname',
            'account_suffix' => '@parp.local',
            //'person_filter' => array('category' => 'objectCategory', 'person' => 'person'),
            'base_dn' => 'dc=parp,dc=local',
            'domain_controllers' => array('10.10.16.21'),
            'admin_username' => 'aktywny_dyrektor',
            'admin_password' => 'abcd@123',
            //'real_primarygroup' => true,
            //'use_ssl' => false,
            //'use_tls' => false,
            //'recursive_groups' => true,
            'ad_port' => '389',
            //'sso' => false,
        );
        $adldap = new \Adldap\Adldap($configuration);
        
        echo("<pre>\n");
        
        $gr = 'SGG-ZZP-PUBLIC-RO';
        $gr = 'INT-BI';
        
        $gr = "marcin_lipinski";
        
        $result = $adldap->group()->find('INT Winadmin');
        print_r($result); die(); 
        
        
        
        $result = $adldap->search()->recursive(false)->where('cn', '=', 'INT Winadmin'/* '=' , 'SGG-ZZP-PUBLIC-RO' */)->get(); //->user()->groups($gr);
        print_r($result); die();   
/*
            $collection = $ad->user()->find('kamil_jakacki');//infoCollection('kamil_jakacki');
            print_r($collection->memberOf);
            print_r($collection->displayName);
        
*/
        
        //$results = $ad->search()->all();
        die('testAdLib2');
    }
    /**
     * @Route("/testAdLib", name="testAdLib")
     * @Template()
     */
    public function testAdLibAction()
    {
        // Create a configuration array.
        $config = [
          'account_suffix'        => '@parp.local',
          'domain_controllers'    => ['10.10.16.21'],
          'base_dn'               => 'dc=parp,dc=local',
          'admin_username'        => 'aktywny_dyrektor',
          'admin_password'        => 'abcd@123',
        ];
        
        // Create a new connection provider.
        $provider = new \Adldap\Connections\Provider($config);
        
        // Construct new Adldap instance.
        $ad = new \Adldap\Adldap();
        
        // Add the provider to Adldap.
        $ad->addProvider('default', $provider);
        
        // Try connecting to the provider.
        try {
            // Connect using the providers name.
            $ad->connect('default');
        
            // Create a new search.
            $search = $provider->search();
        
        
/*
        
            $marcin = $search->where('samaccountname','=','marcin_lipinski')->get();
            
            echo "<br><pre>"; print_r($marcin); echo "</pre>";
            die();
*/

            //"sgg-zzp-public-ro"
            $grupa = $search->recursive(true)->where('CN', '=' , 'INT Winadmin')->get();
        
            echo "<br><pre>"; print_r($grupa->all()); echo "</pre>"; die();
            //echo "<br><pre>"; print_r($grupa); echo "</pre>";
        
        
        
        
        
        
            // Retrieve all groups.
            //$results = $search->groups()->get();
            // This would retrieve all records from AD inside a new Adldap\Objects\Paginator instance.
            $paginator = $search->groups()->paginate(200, 0);
            
            // Returns total number of pages, int
            $paginator->getPages();
            
            // Returns current page number, int
            $paginator->getCurrentPage();
            
            // Returns the amount of entries allowed per page, int
            $paginator->getPerPage();
            
            // Returns all of the results in the entire paginated result
            $results = $paginator->getResults();
            
            // Returns the total amount of retrieved entries, int
            $paginator->count();


            // Iterate over the results like normal
            foreach($paginator as $result)
            {
                echo "<br><pre>"; print_r($result->getMemberNames()); echo "</pre>";
            }
            
            //echo "<pre>"; print_r($results); 
            die();
            
        } catch (\Adldap\Exceptions\Auth\BindException $e) {
        
            // There was an issue binding / connecting to the server.
        
        }
    }
    
    /**
     * @Route("/pokazAll", name="pokazAll")
     * @Template()
     */
    public function pokazAllAction()
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $sqls = [];
        echo "<pre>"; print_r($ADUsers); die();
        
        die();
    }
    /**
     * @Route("/przeniesWszystkich", name="przeniesWszystkich")
     * @Template()
     */
    public function przeniesWszystkichAction()
    {
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ADUsers = $ldap->getAllFromAD();
        $sqls = [];
        //print_r($ADUsers); die();
        $pomijaj = ["chuck_norris", "kamil_wirtualny", "ndes-user", "teresa_oneill", "aktywny_dyrektor", "marcin_lipinski",
        "agnieszka_radomska", "agnieszka_promianows"];
        foreach($ADUsers as $u){
            $sam = str_replace("'", "", $u['samaccountname']);
            if(!in_array($sam, $pomijaj)){
                $sqls[] = "INSERT INTO `entry` (`department`, `distinguishedname`, `fromWhen`, `isImplemented`, `samaccountname`) VALUES
    ('Biuro Administracji', 'CN=".str_replace("'", "", $u['name']).",OU=".$u['description'].",OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST', '2016-07-07 00:00:00', 0, '".$sam."');";
            }
        }
        $sql = implode(" ", $sqls);
        $em = $this->getDoctrine()->getEntityManager();
        $em->getConnection()->exec( $sql );
/*
        $connection = $em->getConnection();
        $statement = $connection->prepare();
        //$statement->bindValue('id', 123);
        $statement->execute();
        $results = $statement->fetchAll();
*/
        
        echo implode("\n\n<br><br>", $sqls);
        die();
        die('przeniesWszystkich');
    }
    /**
     * @Route("/usunWszystkich", name="usunWszystkich")
     * @Template()
     */
    public function usunWszystkichAction()
    {
        if(in_array("PARP_ADMIN", $this->getUser()->getRoles())){
            $ldap = $this->get('ldap_service');
            $ldapAdmin = $this->get('ldap_admin_service');
            $ADUsers = $ldap->getAllFromAD();
            $dns = [];
            //print_r($ADUsers); die();
            $pomijaj = ["chuck_norris", "kamil_wirtualny", "ndes-user", "aktywny_dyrektor", 
            //"marcin_lipinski",
            ];
            foreach($ADUsers as $u){
                $sam = str_replace("'", "", $u['samaccountname']);
                if(!in_array($sam, $pomijaj)){
                    /*$sqls[] = "INSERT INTO `entry` (`department`, `distinguishedname`, `fromWhen`, `isImplemented`, `samaccountname`) VALUES
        ('Biuro Administracji', 'CN=".str_replace("'", "", $u['name']).",OU=".$u['description'].",OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST', '2016-07-07 00:00:00', 0, '".$sam."');";
        */
                    $dn = $u['distinguishedname'];//"CN=".str_replace("'", "", $u['name']).",OU=".$u['description'].",OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST";
                    $dns[] = $dn;
                    $ldapAdmin->deleteEntity($dn);
            
                }
            }
            $sql = "update entry set daneRekord_id = null;delete from entry; delete from dane_rekord;";
            
            $em = $this->getDoctrine()->getEntityManager();
            $em->getConnection()->exec( $sql );
            echo "wykonal sql";
            echo implode("\n\n<br><br>", $dns);
            
        }
        die('usunWszystkichAction');
    }
    
    /**
     * @Route("/ustawManagera", name="ustawManagera")
     * @Template()
     */
    public function ustawManageraAction()
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $sqls = [];
        //print_r($ADUsers); die();
        $pomijaj = ["ndes-user"];//["chuck_norris", "kamil_wirtualny", "ndes-user", "teresa_oneill", "aktywny_dyrektor", "marcin_lipinski", "agnieszka_radomska", "agnieszka_promianows"];
        foreach($ADUsers as $u){
            $sam = str_replace("'", "", $u['samaccountname']);
            if(!in_array($sam, $pomijaj)){
                $sqls[] = "INSERT INTO `entry` (`manager`, `distinguishedname`, `fromWhen`, `isImplemented`, `samaccountname`) VALUES
    ('Aleksjew Martyna', 'CN=".str_replace("'", "", $u['name']).",OU=".$u['description'].",OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST', '2016-07-07 00:00:00', 0, '".$sam."');";
            }
        }
        $sql = implode(" ", $sqls);
        $em = $this->getDoctrine()->getEntityManager();
        $em->getConnection()->exec( $sql );
        
        echo implode("\n\n<br><br>", $sqls);
        die();
        die('ustawManagera');
    }
    /**
     * @Route("/zasobNazwa/{zid}", name="zasobNazwa")
     * @Template()
     */
    public function zasobNazwaAction($zid)
    {
        $n = $this->get('renameService')->zasobNazwa($zid);
        die($n);
        
    }
    
    /** 
     * @Route("/check_user_in_ad/{imienazwisko}", name="check_user_in_ad")
     * @Template()
     */
    public function checkUserInAdAction($imienazwisko)
    {
        $ldap = $this->get('ldap_service');
        //$imienazwisko = $this->get('renameService')->fixImieNazwisko($imienazwisko);
        
        $ADManager = $ldap->getUserFromAD(null, $imienazwisko);
        if(count($ADManager) > 0){
            echo "<br>added ".$ADManager[0]['name']."<br>";
            //$where[$ADManager[0]['name']] = $ADManager[0]['name'];
        }else{
            throw $this->createNotFoundException('Nie moge znalezc wlasciciel zasobu w AD : '.$imienazwisko);
        }
    }
    /**
     * @Route("/check_access/{action}", name="check_access")
     * @Template()
     */
    public function checkAccessAction($action)
    {
        $this->get('check_access')->checkAccess($action);
        
        
        
        $u = $this->getUser();
        echo "Array(\n\t[0] => Użytkownik posiada role:\n)\n";
        print_r($u->getRoles());
        //echo "Array(\n\t[0] => Dostep do akcji:\n)\n";
        //print_r($u->getRoles());
        
        die();
    }

    /**
     * @Route("/index", name="index")
     * @Template()
     */
    public function indexAction()
    {
        die('dev');
    }
    
    /**
     * @Route("/addMissingOUs", name="addMissingOUs")
     * @Template()
     */
    public function addMissingOUsAction()
    {
        $ls = $this->get('ldap_admin_service');
        
        $ls->syncDepartamentsOUs();
        die('testou');
    }
    
    /**
     * @Route("/testou", name="testou")
     * @Template()
     */
    public function testouAction()
    {
        $ls = $this->get('ldap_admin_service');
        
        $u = $ls->getUserFromAD('marcin_lipinski');
        var_dump($u);die();
        
        
        $ls->syncDepartamentsOUs();
        die('testou');
    }
    /**
     * @Route("/generujCreateHistoriaWersji", name="generujCreateHistoriaWersji")
     * @Template()
     */
    public function generujCreateHistoriaWersjiAction()
    {
        $entities = array();
        $em = $this->getDoctrine()->getManager();
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $now = new \Datetime();
        
        $username = "undefined";
        $securityContext = $this->get('security.context');
        if (null !== $securityContext && null !== $securityContext->getToken() && $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $username = ($securityContext->getToken()->getUsername());            
        }
        $request = $this->get('request');
        $url = $request->getUri();
        //print_r($url);
        $route = $request->get('_route');
        
        foreach ($meta as $m) {
            $all = $em->getRepository($m->getName())->findAll();
            $mn = $m->getName();//str_replace("Parp:MainBundle", "ParpMainBundle", str_replace("\\", ":", $m->getName()));
            //print_r($mn); die();
            $all = $result = $this->getDoctrine()
               ->getRepository($mn)
               ->createQueryBuilder('e')
               ->select('e')
               ->getQuery()
               ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
            
            foreach($all as $e){
                $hw = new HistoriaWersji();
                $hw->setAction('create');
                $hw->setLoggedAt($now);
                $hw->setObjectId($e['id']);
                $hw->setObjectClass($m->getName());
                $hw->setVersion(1);
                $d = ($e);
                //print_r($d); die();
                $hw->setData($d);
                $hw->setUsername($username);
                $hw->setUrl($url);
                $hw->setRoute($route);
                $em->persist($hw);
                
            }
            
            $entities[] = array('name' => $m->getName(), 'count' => count($all));
        }
        $em->flush();
        echo "<pre>"; print_r($entities);
        die('generujCreateHistoriaWersji');
    }
    /**
     * @Route("/uzupelnijAdnotacjeHistoriiWersji", name="uzupelnijAdnotacjeHistoriiWersji")
     * @Template()
     */
    public function uzupelnijAdnotacjeHistoriiWersjiAction()
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../../src/*/*/Entity');
        
        $unrelatedClasses = array();
        
        foreach ($finder as $file) {
            if(strpos($file->getRelativePathname(), "~") != strlen($file->getRelativePathname()) -1
            && strstr($file->getRelativePathname(), "Repository") === false
            && strstr($file->getRelativePathname(), "DateEntityClass") === false
            && strstr($file->getRelativePathname(), "OrderItemDTO") === false
            ){
                $f = str_replace(__DIR__."/..../../src", "", $file->getRealpath());
                $f = str_replace("/", "\\", $f);            
                $f = str_replace(".php", "", $f);
                if($f != '\Parp\MainBundle\Entity\HistoriaWersji'){
                    //die($f);
                    $h = file_get_contents($file->getRealpath());
                    
                    if(strstr($h, '@Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")') !== false){
                        echo ('mamy zasob Z gedmo '.$file->getRealpath());
                    }else{
                        echo('mamy zasob bez gedmo '.$file->getRealpath()."<br>\n");
                        $patterns = array (
                            '/( \*\/)(\n)(class)/', 
                            '/(     \*\/)(\n)(    private \$)([^i][^d])/'
                        );
                        $replace = array (
                            ' * @Gedmo\\Mapping\\Annotation\\Loggable(logEntryClass="Parp\\MainBundle\\Entity\\HistoriaWersji")$2$1$2$3', 
                            '     * @Gedmo\\Mapping\\Annotation\\Versioned$2$1$2$3$4'
                        );
                        $h = preg_replace($patterns, $replace, $h);
                        file_put_contents($file->getRealpath(), $h);
                    }
                }
                
                //print_r($h); die();
                
            }
        }
        die('generujCreateHistoriaWersji');
    }
    /**
     * Kasuje wszedzie deletedAt z forms
     *
     * @Route("/fix_forms/",defaults={}, name="dev_fix_forms")
     * @Template()
     */
    public function fixFormsAction()
    {
        $updateFiles = false;
        
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../../src/*/*/Form');
        
        foreach ($finder as $file) {
            // Print the absolute path
            print $file->getRealpath()."\n 1 ";
            $c = file_get_contents($file->getRealpath());
            $deletedAt = true;
            if(strstr($c, "->add('deletedAt',null,array(") === false){
                $deletedAt = false;
            }
            
            if($deletedAt){
                $s = array("->add('deletedAt',null,array(");
                $r = array("->add('deletedAt','hidden',array(");
                $c = str_replace($s, $r, $c);
                if($updateFiles)
                    file_put_contents($file->getRealpath(), $c);
            }
            
            print $file->getRelativePathname()."\n 3 ".($deletedAt ? "ma deleted at" : "NIE MA")." <br/>";
        }
    }

    /**
     * @Route("/groupConcat", name="groupConcat")
     * @Template()
     */
    public function groupConcatAction()
    {
        $sql = "select group_concat(e.samaccountname) from Parp\\MainBundle\\Entity\\Entry e";
        $em = $this->getDoctrine()->getEntityManager();
        $result= $em->createQuery($sql)->getResult();
        \Doctrine\Common\Util\Debug::dump($result);
        die('groupConcat');
    }

    /**
     * @Route("/getUzInfo", name="getUzInfo")
     * @Template()
     */
    public function getUzInfoAction()
    {
        die("getUzInfo");
    }
    
    
    /**
     * @Route("/membersOf", name="membersOf")
     * @Template()
     */
    public function membersOfAction()
    {
                
        // Example Output
         
         
        print_r($this->get_members("INT-BA")); // Gets all members of 'Test Group'
        print_r($this->get_members("INT-BI")); // Gets all users in 'Users'
         
/*
        print_r($this->get_members(
        			array("INT-BI","INT-BA")
        		)); // EXCLUSIVE: Gets only members that belong to BOTH 'Test Group' AND 'Test Group 2'
         
        print_r($this->get_members(
        			array("INT-BI","INT-BA"),TRUE
        		)); // INCLUSIVE: Gets members that belong to EITHER 'Test Group' OR 'Test Group 2'
        
*/
        //$gs = $this->get('ldap_service')->getMembersOfGroupFromAD("INT-BI"); 
        die("membersOf");
    }
    
    protected function get_members($group=FALSE,$inclusive=FALSE) {
        // Active Directory server
        $ldap_host = $this->getParameter('ad_host');
     
        // Active Directory DN
        $ldap_dn_grup = "OU=Grupy,DC=AD,DC=TEST";
        $ldap_dn_userow = "OU=Parp Pracownicy,DC=AD,DC=TEST";
     
        // Domain, for purposes of constructing $user
        $ldap_usr_dom = "@AD.TEST";
        //die($ldap_usr_dom);
        $ldap_username = $this->getParameter('ad_user');
        $ldap_password = $this->getParameter('ad_password');
        // Active Directory user
        $user = $ldap_username;
        $password = $ldap_password;
        //die("$user $password");
     
        // User attributes we want to keep
        // List of User Object properties:
        // http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
        $keep = array(
            "name",
            "mail",
            "initials",
            "title",
            "info",
            "department",
            "description",
            "division",
            "lastlogon",
            "samaccountname",
            "manager",
            "thumbnailphoto",
            "accountExpires",
            "useraccountcontrol",
            "distinguishedName",
            "cn",
            'memberOf',
            'useraccountcontrol'
        );
     
        // Connect to AD
        $ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");
        ldap_bind($ldap,$user.$ldap_usr_dom,$password) or die("Could not bind to LDAP");
     
     	// Begin building query
     	if($group) $query = "(&"; else $query = "";
     
     	$query .= "(objectClass=User)";
     
        // Filter by memberOf, if group is set
        if(is_array($group)) {
        	// Looking for a members amongst multiple groups
        		if($inclusive) {
        			// Inclusive - get users that are in any of the groups
        			// Add OR operator
        			$query .= "(|";
        		} else {
    				// Exclusive - only get users that are in all of the groups
    				// Add AND operator
    				$query .= "(&";
        		}
     
        		// Append each group
        		foreach($group as $g) $query .= "(memberOf=CN=$g,$ldap_dn_grup)";
     
        		$query .= ")";
        } elseif($group) {
        	// Just looking for membership of one group
        	$query .= "(memberOf=CN=$group,$ldap_dn_grup)";
        }
     
        // Close query
        if($group) $query .= ")"; else $query .= "";
     //$query = "cn=$group";
     
     //(&(objectClass=User)(memberOf=CN=myGroup,OU=MyContainer,DC=myOrg,DC=local))
    	// Uncomment to output queries onto page for debugging
    	print_r($query);
     
        // Search AD
        $results = ldap_search($ldap,$ldap_dn_userow,$query);
        $entries = ldap_get_entries($ldap, $results);
     
        // Remove first entry (it's always blank)
        array_shift($entries);
     
        $output = array(); // Declare the output array
     
        $i = 0; // Counter
        // Build output array
        foreach($entries as $u) {
            foreach($keep as $x) {
            	// Check for attribute
        		if(isset($u[$x][0])) $attrval = $u[$x][0]; else $attrval = NULL;
     
            	// Append attribute to output array
            	$output[$i][$x] = $attrval;
            }
            $i++;
        }
     
        return $output;
    }
 
    /**
     * @Route("/getAdGroups", name="getAdGroups")
     * @Template()
     */
    public function getAdGroupsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        
        $exists = $this->get('ldap_service')->checkGroupExistsFromAD(null);
                
    }
    
    
    /**
     * @Route("/checkAdGroups", name="checkAdGroups")
     * @Template()
     */
    public function checkAdGroupsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $zasoby = $em->getRepository('ParpMainBundle:Zasoby')->findAll();
        $ret = array('sa' => array(), 'sa multi' => array(), 'nie ma' => array());
        $retall = array();
        $itek = 0;
        foreach($zasoby as $z){
            if($itek++ < 100000){
                //echo ".".substr($z->getNazwa(), 0, 2).".";
                //echo($z->parseZasobGroupName());
                $exists = $this->get('ldap_service')->checkGroupExistsFromAD($z->parseZasobGroupName());
                
                $existsRO = $this->get('ldap_service')->checkGroupExistsFromAD($z->parseZasobGroupName()."-RO");
                $existsRW = $this->get('ldap_service')->checkGroupExistsFromAD($z->parseZasobGroupName()."-RW");
                $existsP = $this->get('ldap_service')->checkGroupExistsFromAD($z->parseZasobGroupName()."-P");
                
                $liczba = ($exists ? 1 : 0) + ($existsRO ? 1 : 0) + ($existsRW ? 1 : 0) + ($existsP ? 1 : 0);
                
                
                
                if($liczba == 0){
                    $ret['nie ma']["'".$z->getNazwa()."'"] = "'".$z->parseZasobGroupName()."' ani -RO ani -RW ani -P";
                }else{
                    if($liczba == 1){
                        $ret['sa']["'".$z->getNazwa()."'"] = "'".$z->parseZasobGroupName()."'";
                    }else{
                        $sa = $exists ? "'".$z->parseZasobGroupName()."'" : " ";
                        $sa .= $existsRO ? "'".$z->parseZasobGroupName()."-RO"."'" : " ";
                        $sa .= $existsRW ? "'".$z->parseZasobGroupName()."-RW"."'" : " ";
                        $sa .= $existsP ? "'".$z->parseZasobGroupName()."-P"."'" : " ";
                        $ret['sa multi']["'".$z->getNazwa()."'"] = $sa;
                    }
                }
            }
        }
        $html = '<div class="panel-group" id="accordion">';
        $html .= '<div class="panel panel-default">
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">Nie istnieja w AD (pojedyncze grupy) - '.count($ret['nie ma']).':</a>
                      </h4>
                    </div>';
        $html .= '<div id="collapse1" class="panel-collapse collapse in"><div class="panel-body"><pre>'.print_r($ret['nie ma'], true)."</pre></div></div>";
        
        $html .= '<div class="panel panel-default">
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse3">Istnieja w AD (pojedyncze grupy) - '.count($ret['sa']).':</a>
                      </h4>
                    </div>';
        $html .= '<div id="collapse3" class="panel-collapse collapse"><div class="panel-body"><pre>'.print_r($ret['sa'], true)."</pre></div></div>";
        
        $html .= '<div class="panel panel-default">
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse4">Istnieja w AD (multi grupy) - '.count($ret['sa multi']).':</a>
                      </h4>
                    </div>';
        $html .= '<div id="collapse4" class="panel-collapse collapse"><div class="panel-body"><pre>'.print_r($ret['sa multi'], true)."</pre></div></div>";
        
        $html .= "</div>";        
/*
        echo "</pre><h1>Nie istnieja w AD (SG grupy):</h1><pre>";
        
        print_r($ret['nie ma SG']);
        
        echo "</pre><h1>Istnieja w AD (pojedyncze grupy):</h1><pre>";
        
        print_r($ret['sa SG']);
        
        echo "</pre><h1>Istnieja w AD (SG grupy):</h1><pre>";
        
        print_r($ret['sa']);
*/
        
        
        
        return array('html' => $html);    
    }
    protected function parseZasobGroupName(){
        
    }
    
    
    
    
    /**
     * @Route("/fixZasobyWlascicieliAdminow", name="fixZasobyWlascicieliAdminow")
     * @Template()
     */
    public function fixZasobyWlascicieliAdminowAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $zasoby = $em->getRepository('ParpMainBundle:Zasoby')->findAll();
        $i = 0;
        foreach($zasoby as $z){
            $r = $this->fixLudzi($z->getWlascicielZasobu());
            $z->setWlascicielZasobuEcm($z->getWlascicielZasobu());
            $z->setWlascicielZasobuZgubieni($r['zgubieni']);
            $z->setWlascicielZasobu($r['ludzie']);
            
            $r = $this->fixLudzi($z->getAdministratorZasobu());
            $z->setAdministratorZasobuEcm($z->getAdministratorZasobu());
            $z->setAdministratorZasobuZgubieni($r['zgubieni']);
            $z->setAdministratorZasobu($r['ludzie']);
            
            $r = $this->fixLudzi($z->getAdministratorTechnicznyZasobu());
            $z->setAdministratorTechnicznyZasobuEcm($z->getAdministratorTechnicznyZasobu());
            $z->setAdministratorTechnicznyZasobuZgubieni($r['zgubieni']);
            $z->setAdministratorTechnicznyZasobu($r['ludzie']);
            
            $i++;
            if($i > 15000)
                die('nie doszedl do konca 15000');
        }
        $em->flush();
                
    }
    protected function fixLudzi($ludzie){
        $pomijaj = ["Aktywny Dyrektor"];
        $ret = [];
        $zgubieni = [];
        $ldap = $this->get('ldap_service');
        //przerobic ich na samaccountnames
        $larr = explode(",", $ludzie);
        foreach($larr as $l){
            $l = trim($l);
            //na razie pomija ytych z nazwiskami w nawiasach
            //TODO: poprawich tych z nazwiskami w nawiasach 
            if(
                //pomijam puste
                $l != "" && 
                //pomijam ustalone wyzej
                !in_array($l, $pomijaj) && 
                //pomijam te z nazwiskiem w nawiasie
                //strstr($l, "(") === false && 
                //pomijam loginy (czyli to co juz jest ok)
                strstr($l, "_") === false
            ){
                echo ("Szukam osoby ".$l."<br>");
                $ADuser = $ldap->getUserFromAD(null, $l); 
                if(count($ADuser) > 0){
                    $ret[] = $ADuser[0]['samaccountname'];
                }else{
                    $zgubieni[] = $l;
                    echo ("Blad 54367 nie moge znalezc osoby ".$l."<br>");
                }
                
            }
        }
        return ['ludzie' => implode(",", $ret), 'zgubieni' => implode(",", $zgubieni) ];
    }
    
    /**
     * @Route("/fixZasobyWlascicieliAdminowNadajMultiRole", name="fixZasobyWlascicieliAdminowNadajMultiRole")
     * @Template()
     */
    public function fixZasobyWlascicieliAdminowNadajMultiRoleAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $zasoby = $em->getRepository('ParpMainBundle:Zasoby')->findAll();
        
        $rolaWlasciciel = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_WLASCICIEL_ZASOBOW');
        $rolaAdministrator = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_ADMIN_ZASOBOW');
        $rolaAdministratorTechniczny = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_ADMIN_TECHNICZNY_ZASOBOW');
        
        $i = 0;
        foreach($zasoby as $z){
            $this->dodajRole($z->getWlascicielZasobu(), $rolaWlasciciel);
            $this->dodajRole($z->getAdministratorZasobu(), $rolaAdministrator);
            $this->dodajRole($z->getAdministratorTechnicznyZasobu(), $rolaAdministratorTechniczny);            
        }
        $em->flush();
    }
    
    protected function dodajRole($ludzie, $rola){
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $arr = explode(",", $ludzie);
        foreach($arr as $l){
            $l = trim($l);
            if($l != ""){
                $ur = new \Parp\MainBundle\Entity\AclUserRole();
                $ur->setRole($rola);
                $ur->setSamaccountname($l);
                $em->persist($ur);
            }
        }
        
    }
    
    
    /**
     * @Route("/fixLogins", name="fixLogins")
     * @Template()
     */
    public function fixLoginsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $dr = $em->getRepository('ParpMainBundle:DaneRekord')->findAll();
        foreach($dr as $d){
            $login = $this->get('samaccountname_generator')->generateSamaccountname($d->getImie(), $d->getNazwisko(), false);
            $d->setLogin($login);
        }
        $em->flush();
        
    }
    
    
    /**
     * @Route("/findNamesChangedAndFix", name="findNamesChangedAndFix")
     * @Template()
     */
    public function findNamesChangedAndFixAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        //$name = "Boceńska (Burakowska) Iwona";
        //die("tylko_nowe_nazwisko");
        
        $users = $this->get('ldap_service')->getAllFromAD(true);
        $i = 0;
        $errors = [];
        $zmienilem = [];
        for($i = 0; $i < count($users); $i++){
            unset($users[$i]['thumbnailphoto']);
            $u = $users[$i];
            if(strstr($u['name'], "(") !== false){
                $name = $u['name'];
                $tylko_nowe_nazwisko = substr($name, 0, strpos($name, "("));
                $imie = substr($name, strpos($name, ")")+1);
                $u['tylko_nowe_nazwisko'] = $tylko_nowe_nazwisko;
                $u['imie'] = $imie;
            }else{
                
                $name = $u['name'];
                $tylko_nowe_nazwisko = substr($name, 0, strpos($name, " "));
                $imie = substr($name, strpos($name, " ")+1);
                $u['tylko_nowe_nazwisko'] = $tylko_nowe_nazwisko;
                $u['imie'] = $imie;
            }
            
            $dr = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy(['nazwisko' => trim($u['tylko_nowe_nazwisko']), 'imie' => trim($u['imie'])]);
            if(!$dr){
                //echo "<pre>"; print_r($z);
                $errors[] = ("Nie mam danych rekord dla osoby ".$u['name']);
            }else{
                //echo "<br>zmieniam login dla ".$z['name']." na ".$z['samaccountname'];
                $zmienilem[$dr->getLogin()] = $u['samaccountname'];
                $dr->setLogin($u['samaccountname']);
            }
            //echo "<pre>"; print_r($u); die();
        }
        //echo "<pre>"; print_r($zmienione); die();
        echo "<pre>"; print_r($errors); echo "</pre>";
        echo "<pre>"; print_r($zmienilem); echo "</pre>";
        //echo "<pre>"; print_r($zmienione); echo "</pre>"; 
        //die();
        $em->flush();
    }
    
    
    
    /**
     * @Route("/checkKrakowiak", name="checkKrakowiak")
     * @Template()
     */
    public function checkKrakowiakAction()
    {
        $users = $this->get('ldap_service')->getAllFromAD(true);
        foreach($users as &$u){
            unset($u['thumbnailphoto']);
        }
        echo "<pre>"; print_r($users); die();
    }
    
    /**
     * @Route("/getAllUsers", name="getAllUsers")
     * @Template()
     */
    public function getAllUsersAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        //$name = "Boceńska (Burakowska) Iwona";
        //die("tylko_nowe_nazwisko");
        
        $users = $this->get('ldap_service')->getAllFromAD(false, true);
        $zostaliBezRekorda = [];
        $zostaliZRekorda = [];
        foreach($users as $u){
            $dr = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy(['login' => trim($u['samaccountname'])]);
            if($dr){
                $zostaliZRekorda[$u['samaccountname']] = $u['name'];
            }else{
                
                $zostaliBezRekorda[$u['samaccountname']] = $u['name'];
            }
            
        }
        
        echo "<pre>"; print_r($zostaliZRekorda); echo "<pre>"; 
        echo "<pre>"; print_r($zostaliBezRekorda); echo "<pre>"; 
        die();
        
    }
    
    
    /**
     * @Route("/getAllUsersBySection", name="getAllUsersBySection")
     * @Template()
     */
    public function getAllUsersBySectionAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $pomijac = ["n/d","ND"];
        //$name = "Boceńska (Burakowska) Iwona";
        //die("tylko_nowe_nazwisko");
        $podzialNaDepISekcje = [];
        $pominieci = [];
        $sekcje = [];
        $users = $this->get('ldap_service')->getAllFromAD(false, false);
        foreach($users as &$u){
            unset($u['thumbnailphoto']);
            $dep = $this->getOUfromDN($u);
            if($dep != "" && $u['division'] != "" && !in_array($u['division'], $pomijac)){
                $podzialNaDepISekcje[$dep][$u['division']] = $u;
                $sekcje[$u['division']] = $u['division'];
            }else{
                $pominieci[] = $u;
            }
        }
        print_r($sekcje);
        print_r($podzialNaDepISekcje);
        print_r($pominieci);
        die();
    }
    
    
    /**
     * @Route("/fixKierownikowSekcji", name="fixKierownikowSekcji")
     * @Template()
     */
    public function fixKierownikowSekcjiAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
            
        $dr = $em->getRepository('ParpMainBundle:Section')->findAll();
        foreach($dr as $d){
            $kieroDn = $d->getKierownikDN();
            $ndn = $this->fixKierownikOU($kieroDn, $d->getDepartament()->getShortname());
            echo ("<br>".$kieroDn."<br>".$ndn);
            $d->setKierownikDN($ndn);
        }
        $em->flush();
    }
    
    protected function fixKierownikOU($dn, $depOu){
        $p = explode(",", $dn);
        $ret = [];
        for($i = 0; $i < count($p); $i++){
            if($i == 1){
                $ret[] = "OU=".$depOu;
            }else{
                $ret[] = $p[$i];
            }
        }
        return implode(",", $ret);
    }
    
    
    /**
     * @Route("/getAllUsersTable", name="getAllUsersTable")
     * @Template()
     */
    public function getAllUsersTableAction()
    {
        $users = $this->get('ldap_service')->getAllFromAD(false, false);
        foreach($users as &$u){
            unset($u['thumbnailphoto']);
            //unset($u['manager']);
            //unset($u['memberOf']);
        }
        //print_r($users); die();
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $users]);
    }
    
    
    /**
     * @Route("/testMerge", name="testMerge")
     * @Template()
     */
    public function testMergeAction()
    {
        $wlasciciel1 = "kamil_jakacki,robertt_muchacki";
        $wlasciciel2 = "artur_marszalek,andrzej_trocewicz";
        
        $a1 = explode(",", $wlasciciel1);
        $a2 = explode(",", $wlasciciel2);
        
        $a3 = array_merge($a1, $a2);
        
        echo "<pre>"; print_r($a3); die();
    }
    
    /**
     * @Route("/fixWlasciciele", name="fixWlasciciele")
     * @Template()
     */
    public function fixWlascicieleAction()
    {
        $ldap = $this->get('ldap_service');
        
        $em = $this->getDoctrine()->getManager();
        $wynik = [];
        
        $zass = $em->getRepository('ParpMainBundle:Zasoby')->findByPublished(1);
        //die(".".count($zass));
        foreach($zass as $z){
            $w1 = $z->getWlascicielZasobu();
            $ws1 = explode(",", $w1);
            $w2 = $z->getPowiernicyWlascicielaZasobu();
            $ws2 = explode(",", $w2);
            $blad = [];
            
            if(count($ws1) == 0){
                $blad[] = "Nie mam wlascicieli zasobu !!!!!";
            }
            //wlasciciel jest brany jako pierwszy , chyba ze znajdzie dyra ponizej
            $wlascicielI = 0;
            //szuka dyrektora
            for($i = 0; $i < count($ws1); $i++){                
                $w = $ws1[$i];
                $u = $ldap->getUserFromAD($w);
                if($u){
                    $stanowisko = $u[0]['title'];
                    if(strstr($stanowisko, "yrektor") !== false){
                        if($wlascicielI != 0){
                            //mam drugiego dyra!!!
                            $blad[] = "Mam kilku dyrektorow!!!";
                        }
                        $wlascicielI = $i;
                    }
                }else{
                    $blad[] = "Nie moge znalezc usera w AD ".$w."!!!";
                }
            }
            if($wlascicielI == 0){                
                $blad[] = "Nie znalazlem dyrektora , biore pierwsza osobe!!!";
            }
            
            
            
            $wlasciciel = $ws1[$wlascicielI];
            $powiernicy = [];
            for($i = 0; $i < count($ws1); $i++){
                if($ws1[$i] != "" && $i == $wlascicielI)
                    $powiernicy[] = $ws1[$i];
            }
            for($i = 0; $i < count($ws2); $i++){
                if($ws2[$i] != "")
                    $powiernicy[] = $ws2[$i];
            }
            
            $wynik[] = [
                'zasobId' => $z->getId(),
                'nazwa' => $z->getNazwa(),
                'wlascicielOrg' => $z->getWlascicielZasobu(),
                'powiernicyOrg' => $z->getPowiernicyWlascicielaZasobu(),
                'wlasciciel' => $wlasciciel,
                'powiernicy' => implode(",", $powiernicy),
                'blad' => implode(",", $blad)
            ];
            $z->setWlascicielZasobu($wlasciciel);
            $z->setPowiernicyWlascicielaZasobu(implode(",", $powiernicy));
            //echo "<pre>"; $z->getId(); echo "</pre>";
            //echo "<pre>"; print_r($ws1); echo "</pre>";
            //echo "<pre>"; print_r($ws2); echo "</pre>";
        }
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wynik]);
        //die();
    }
    /**
     * @Route("/getDyrektors", name="getDyrektors")
     * @Template()
     */
    public function getDyrektorsAction()
    {
        $ldap = $this->get('ldap_service');
        $dyrs = $ldap->getDyrektorow();
        $ret = [];
        foreach($dyrs as $d){
            $ret[] = [
                'departament' => $d['department'],
                'dyrektor' => $d['name'],
                'login' => $d['samaccountname']
            ];
        }
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
    }
    
    /**
     * @Route("/getGrupyDepartamentu/{departament}", name="getGrupyDepartamentu")
     * @Template()
     */
    public function getGrupyDepartamentuAction($departament)
    {
        $tab = explode(".", $this->container->getParameter('ad_domain'));
        $configuration = array(
            //'user_id_key' => 'samaccountname',
            'account_suffix' => '@' . $this->container->getParameter('ad_domain'),
            //'person_filter' => array('category' => 'objectCategory', 'person' => 'person'),
            'base_dn' => 'DC=' . $tab[0] . ',DC=' . $tab[1],
            'domain_controllers' => array($this->container->getParameter('ad_host'),$this->container->getParameter('ad_host2'),$this->container->getParameter('ad_host3')),
            'admin_username' => $this->container->getParameter('ad_user'),
            'admin_password' => $this->container->getParameter('ad_password'),
            //'real_primarygroup' => true,
            //'use_ssl' => false,
            //'use_tls' => false,
            //'recursive_groups' => true,
            'ad_port' => '389',
            //'sso' => false,
        );
        //var_dump($configuration);
        $adldap = new \Adldap\Adldap($configuration);
        $grupa = "SGG-".$departament;
        //$g = $adldap->group()->find($grupa);
        //$g = $adldap->group()->search(null, false, "INT-BI");
        $g = $this->get('ldap_service')->getGroupsFromAD($grupa, "*");
        
        $ret = [];
        foreach($g as $k => $r){
            //var_dump(substr($r['name'][0], strlen($r['name'][0]) - 3, 3));
            if(substr($r['name'][0], strlen($r['name'][0]) - 3, 3) == "-RW")
                $ret[] = $r['name'];
        }
        
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
        
        //var_dump(($g));
    }
    /**
     * @Route("/martynaMiszczyk", name="martynaMiszczyk")
     * @Template()
     */
    public function martynaMiszczykAction()
    {
        $ldap = $this->get('ldap_service');
        
        $ADManager = [$ldap->getDyrektoraDepartamentu("BP")];
        print_r($ADManager); die();
        
        
        $us = $ldap->getUserFromAD('martyna_miszczyk');
        $ADUser = $us[0];
         $in1 = mb_stripos($ADUser['manager'], '=') + 1;
        $in2 = mb_stripos($ADUser['manager'], ',OU');
        $in3 = (mb_stripos($ADUser['manager'], '=') + 1);
        var_dump($in1, $in2, $in3);
        $mgr = mb_substr($ADUser['manager'], $in1, ($in2) - $in3);
        $mancn = str_replace("CN=", "", substr($mgr, 0, stripos($mgr, ',')));
        $ADManager = $ldap->getUserFromAD(null, $mgr);
        echo "<pre>"; print_r($ADManager); die();
    }
    /**
     * @Route("/testUprawnien", name="testUprawnien")
     * @Template()
     */
    public function testUprawnienAction()
    {
        $ldap = $this->get('ldap_admin_service');
        
        die('a');    
        
    }
    
    /**
     * @Route("/testSekcjiNowych", name="testSekcjiNowych")
     * @Template()
     */
    public function testSekcjiNowychAction()
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $u = $ldap->getUserFromAD('wioletta_skrzypczyns');
        
        $imieNazwisko = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($u[0]['name']);
        $danerekord = $em->getRepository('ParpMainBundle:DaneRekord')->findBy(
        ['imie' => $imieNazwisko[1], 
        //'nazwisko' => $imieNazwisko[0]
        ]);
                
        var_dump($imieNazwisko, $danerekord); die();
    }
    
    /**
     * @Route("/updateDyrektorowZasobow", name="updateDyrektorowZasobow")
     * @Template()
     */
    public function updateDyrektorowZasobowAction()
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $deps = $em->getRepository("ParpMainBundle:Departament")->findByNowaStruktura(1);
        $wyniki = [];
        foreach($deps as $d){
            $dyr = $ldap->getDyrektoraDepartamentu($d->getShortname());
            if($dyr){
                if($d->getDyrektor() != $dyr['samaccountname']){
                    //$wyniki[] = ['id' => $d->getId(), 'msg' => "<br>zmiana dyr z ".$d->getDyrektor()." na ".$dyr['samaccountname']];
                    $d->setDyrektor($dyr['samaccountname']);
                    $d->setDyrektorDN($dyr['distinguishedname']);
                }else{
                    //$wyniki[] = ['id' => $d->getId(), 'msg' => "<br>bez zmian  dyr z ".$d->getDyrektor()." na ".$dyr['samaccountname']];
                }
                
            }else{
                //$wyniki[] = ['id' => $d->getId(), 'msg' => "<br>brak dyra"];
                
            }
            $zasoby = $em->getRepository("ParpMainBundle:Zasoby")->findByKomorkaOrgazniacyjna($d->getName());
            foreach($zasoby as $z){
                if($z->getWlascicielZasobu() != $dyr['samaccountname']){
                    $wyniki[] = ['departament' => $d->getName(), 'zasob_id' => $z->getNazwa(), 
                    'stary_wlasciciel' => $z->getWlascicielZasobu(),
                    'nowy_wlasciciel' => $dyr['samaccountname'],
                    'msg' => "zmiana  wlasciciela z ".$z->getWlascicielZasobu()." na ".$dyr['samaccountname']];  
                    
                    $z->setWlascicielZasobu($dyr['samaccountname']);
                      
                    //
                }else{
                    $wyniki[] = ['departament' => $d->getName(), 'zas_id' => $z->getNazwa(), 
                    'stary_wlasciciel' => $z->getWlascicielZasobu(),
                    'nowy_wlasciciel' => $dyr['samaccountname'],
                    'msg' => "Bez zmian ".$z->getWlascicielZasobu()." na ".$dyr['samaccountname']]; 
                    
                }
            }
            
        } 
        //$em->flush();
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wyniki]);
        
    }
    
    /**
     * @Route("/updateZasobyDepartamanty", name="updateZasobyDepartamanty")
     * @Template()
     */
    public function updateZasobyDepartamantyAction(){
        $zamienBiura = [
            4376 =>'Biuro Zarządzania Kadrami',
            4377 =>'Biuro Zarządzania Kadrami',
            4366 =>'Departament Usług Proinnowacyjnych',
            4368 =>'Departament Usług Proinnowacyjnych',
            4391 =>'Departament Usług Proinnowacyjnych',
            4392 =>'Departament Usług Proinnowacyjnych',
            4393 =>'Departament Usług Proinnowacyjnych',
            4394 =>'Departament Usług Proinnowacyjnych',
            4395 =>'Departament Usług Proinnowacyjnych',
            4421 =>'Departament Usług Proinnowacyjnych',
            4422 =>'Departament Usług Proinnowacyjnych',
            4423 =>'Departament Usług Proinnowacyjnych',
            4424 =>'Departament Usług Proinnowacyjnych',
            4425 =>'Departament Usług Proinnowacyjnych',
            4454 =>'Departament Usług Proinnowacyjnych',
            4455 =>'Departament Usług Proinnowacyjnych',
            4456 =>'Departament Usług Proinnowacyjnych',
            4457 =>'Departament Usług Proinnowacyjnych',
            4458 =>'Departament Usług Proinnowacyjnych',
            4460 =>'Departament Usług Proinnowacyjnych',
            4461 =>'Departament Usług Proinnowacyjnych',
            4462 =>'Departament Usług Proinnowacyjnych',
            4463 =>'Departament Usług Proinnowacyjnych',
            4464 =>'Departament Usług Proinnowacyjnych',
            4465 =>'Departament Usług Proinnowacyjnych',
            4466 =>'Departament Usług Proinnowacyjnych',
            4467 =>'Departament Usług Proinnowacyjnych',
            4468 =>'Departament Usług Proinnowacyjnych',
            4469 =>'Departament Usług Proinnowacyjnych',
            4478 =>'Departament Usług Proinnowacyjnych',
            3608 =>'Departament Usług Proinnowacyjnych',
            3610 =>'Departament Usług Proinnowacyjnych',
            3355 =>'Departament Usług Proinnowacyjnych',
            3356 =>'Departament Usług Proinnowacyjnych',
            3357 =>'Departament Usług Proinnowacyjnych',
            3358 =>'Departament Usług Proinnowacyjnych',
            3359 =>'Departament Usług Proinnowacyjnych',
            3884 =>'Departament Usług Proinnowacyjnych',
            3885 =>'Departament Usług Proinnowacyjnych',
            3886 =>'Departament Usług Proinnowacyjnych',
            3889 =>'Departament Usług Proinnowacyjnych',
            3890 =>'Departament Usług Proinnowacyjnych',
            3744 =>'Departament Usług Proinnowacyjnych',
            3254 =>'Departament Usług Proinnowacyjnych',
            3262 =>'Departament Usług Proinnowacyjnych',
            3528 =>'Departament Usług Proinnowacyjnych',
            3531 =>'Departament Usług Proinnowacyjnych',
            4078 =>'Departament Usług Proinnowacyjnych',
            4334 =>'Departament Usług Proinnowacyjnych',
            4079 =>'Departament Usług Proinnowacyjnych',
            4080 =>'Departament Usług Proinnowacyjnych',
            4081 =>'Departament Usług Proinnowacyjnych',
            4082 =>'Departament Usług Proinnowacyjnych',
            4342 =>'Departament Usług Proinnowacyjnych',
            3362 =>'Departament Usług Proinnowacyjnych',
            3377 =>'Departament Usług Proinnowacyjnych',
            3437 =>'Departament Usług Proinnowacyjnych',
            3529 =>'Departament Usług Proinnowacyjnych',
            3718 =>'Departament Usług Proinnowacyjnych',
            3745 =>'Departament Usług Proinnowacyjnych',
            3716 =>'Departament Usług Proinnowacyjnych',
            4397 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4398 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4399 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4400 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4401 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4402 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4475 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4481 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4482 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4096 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4097 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4098 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4099 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3615 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3617 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3365 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3368 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3369 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3370 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4200 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4201 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4202 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4203 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3699 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3731 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3740 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3253 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4335 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4336 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4343 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4089 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4091 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4092 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4093 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4094 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4095 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3407 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3865 =>'???',
            4369 =>'Biuro Administracji',
            4370 =>'Biuro Administracji',
            4412 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4413 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4431 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4432 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4111 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4117 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4118 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3626 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3383 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3909 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3910 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3911 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3913 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3914 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3915 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3711 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3279 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3306 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3307 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3308 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3309 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3570 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4137 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            4166 =>'Departament Wdrożeń Innowacji w Przedsiębiorstwach',
            3614 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3616 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4221 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4222 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4224 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4225 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4226 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4227 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4228 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3484 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3485 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            3486 =>'Departament Rozwoju Kadr w Przedsiębiorstwach',
            4378 =>'Departament Analiz i Strategii',
            4103 =>'Departament Analiz i Strategii',
            4105 =>'Departament Analiz i Strategii',
            3618 =>'Departament Analiz i Strategii',
            3620 =>'Departament Analiz i Strategii',
            3897 =>'Departament Analiz i Strategii',
            3898 =>'Departament Analiz i Strategii',
            3903 =>'Departament Analiz i Strategii',
            3904 =>'Departament Analiz i Strategii',
            3460 =>'Departament Analiz i Strategii',
            3461 =>'Departament Analiz i Strategii',
            4386 =>'Departament Koordynacji Wdrażania Programów',
            4420 =>'Departament Koordynacji Wdrażania Programów',
            4443 =>'Departament Koordynacji Wdrażania Programów',
            4490 =>'Departament Koordynacji Wdrażania Programów',
            4494 =>'Departament Koordynacji Wdrażania Programów',
            4056 =>'Departament Koordynacji Wdrażania Programów',
            4059 =>'Departament Koordynacji Wdrażania Programów',
            4367 =>'Departament Finansowo-Księgowy',
            4480 =>'Departament Finansowo-Księgowy',
            4484 =>'Departament Finansowo-Księgowy',
            4388 =>'Departament Projektów Infrastrukturalnych',
            4389 =>'Departament Projektów Infrastrukturalnych',
            4390 =>'Departament Projektów Infrastrukturalnych',
            3439 =>'Departament Projektów Infrastrukturalnych',
            3440 =>'Departament Projektów Infrastrukturalnych',
            3441 =>'Departament Projektów Infrastrukturalnych',
            3442 =>'Departament Projektów Infrastrukturalnych',
            3444 =>'Departament Projektów Infrastrukturalnych',
            3717 =>'Departament Projektów Infrastrukturalnych',
            3741 =>'Departament Projektów Infrastrukturalnych',
            3742 =>'Departament Projektów Infrastrukturalnych',
            4330 =>'Departament Projektów Infrastrukturalnych',
            4381 =>'Departament Kontroli',
            4382 =>'Departament Kontroli',
            4383 =>'Departament Kontroli',
            4384 =>'Departament Kontroli',
            4385 =>'Departament Kontroli',
            4418 =>'Departament Kontroli',
            4426 =>'Departament Kontroli',
            4427 =>'Departament Kontroli',
            4428 =>'Departament Kontroli',
            4450 =>'Departament Kontroli',
            4472 =>'Departament Kontroli',
            4479 =>'Departament Kontroli',
            4053 =>'Departament Kontroli',
            4135 =>'Departament Kontroli',
            4172 =>'Departament Kontroli',
            4403 =>'Departament Rozwoju Startapów',
            4404 =>'Departament Rozwoju Startapów',
            4493 =>'Departament Rozwoju Startapów',
            4138 =>'Departament Rozwoju Startapów',
            4140 =>'Departament Rozwoju Startapów',
            4141 =>'Departament Rozwoju Startapów',
            4142 =>'Departament Rozwoju Startapów',
            4144 =>'Departament Rozwoju Startapów',
            3634 =>'Departament Rozwoju Startapów',
            3635 =>'Departament Rozwoju Startapów',
            3636 =>'Departament Rozwoju Startapów',
            3637 =>'Departament Rozwoju Startapów',
            3638 =>'Departament Rozwoju Startapów',
            3639 =>'Departament Rozwoju Startapów',
            3640 =>'Departament Rozwoju Startapów',
            3641 =>'Departament Rozwoju Startapów',
            3643 =>'Departament Rozwoju Startapów',
            3645 =>'Departament Rozwoju Startapów',
            3399 =>'Departament Rozwoju Startapów',
            3400 =>'Departament Rozwoju Startapów',
            3403 =>'Departament Rozwoju Startapów',
            3404 =>'Departament Rozwoju Startapów',
            3405 =>'Departament Rozwoju Startapów',
            3406 =>'Departament Rozwoju Startapów',
            3934 =>'Departament Rozwoju Startapów',
            3714 =>'Departament Rozwoju Startapów',
            4232 =>'Departament Rozwoju Startapów',
            4233 =>'Departament Rozwoju Startapów',
            4236 =>'Departament Rozwoju Startapów',
            4239 =>'Departament Rozwoju Startapów',
            3746 =>'Departament Rozwoju Startapów',
            3493 =>'Departament Rozwoju Startapów',
            3522 =>'Departament Rozwoju Startapów',
            3524 =>'Departament Rozwoju Startapów',
            3274 =>'Departament Rozwoju Startapów',
            4387 =>'Departament Promocji Gospodarczej',
            4379 =>'Departament Komunikacji i Marketingu',
            4380 =>'Departament Komunikacji i Marketingu',
            4497 =>'Departament Komunikacji i Marketingu',
            3330 =>'Departament Komunikacji i Marketingu',
            3331 =>'Departament Komunikacji i Marketingu',
            3332 =>'Departament Komunikacji i Marketingu',
            3333 =>'Departament Komunikacji i Marketingu',
            3337 =>'Departament Komunikacji i Marketingu',
            3595 =>'Departament Komunikacji i Marketingu',
            3341 =>'Departament Komunikacji i Marketingu',
            3342 =>'Departament Komunikacji i Marketingu',
            3598 =>'Departament Komunikacji i Marketingu',
            3343 =>'Departament Komunikacji i Marketingu',
            3599 =>'Departament Komunikacji i Marketingu',
            3600 =>'Departament Komunikacji i Marketingu',
            3345 =>'Departament Komunikacji i Marketingu',
            3878 =>'Departament Komunikacji i Marketingu',
            3879 =>'Departament Komunikacji i Marketingu',
            3661 =>'Departament Komunikacji i Marketingu',
            3662 =>'Departament Komunikacji i Marketingu',
            4190 =>'Departament Komunikacji i Marketingu',
            4191 =>'Departament Komunikacji i Marketingu',
            4192 =>'Departament Komunikacji i Marketingu',
            4193 =>'Departament Komunikacji i Marketingu',
            4194 =>'Departament Komunikacji i Marketingu',
            4195 =>'Departament Komunikacji i Marketingu',
            4196 =>'Departament Komunikacji i Marketingu',
            3686 =>'Departament Komunikacji i Marketingu',
            3943 =>'Departament Komunikacji i Marketingu',
            3703 =>'Departament Komunikacji i Marketingu',
            3463 =>'Departament Komunikacji i Marketingu',
            3465 =>'Departament Komunikacji i Marketingu',
            3466 =>'Departament Komunikacji i Marketingu',
            3468 =>'Departament Komunikacji i Marketingu',
            3470 =>'Departament Komunikacji i Marketingu',
            3726 =>'Departament Komunikacji i Marketingu',
            3983 =>'Departament Komunikacji i Marketingu',
            3984 =>'Departament Komunikacji i Marketingu',
            3730 =>'Departament Komunikacji i Marketingu',
            3986 =>'Departament Komunikacji i Marketingu',
            3987 =>'Departament Komunikacji i Marketingu',
            3988 =>'Departament Komunikacji i Marketingu',
            3735 =>'Departament Komunikacji i Marketingu',
            3737 =>'Departament Komunikacji i Marketingu',
            3739 =>'Departament Komunikacji i Marketingu',
            3252 =>'Departament Komunikacji i Marketingu',
            3519 =>'Departament Komunikacji i Marketingu',
            3520 =>'Departament Komunikacji i Marketingu',
            3265 =>'Departament Komunikacji i Marketingu',
            3266 =>'Departament Komunikacji i Marketingu',
            3523 =>'Departament Komunikacji i Marketingu',
            3268 =>'Departament Komunikacji i Marketingu',
            3270 =>'Departament Komunikacji i Marketingu',
            3271 =>'Departament Komunikacji i Marketingu',
            3272 =>'Departament Komunikacji i Marketingu',
            3273 =>'Departament Komunikacji i Marketingu',
            3275 =>'Departament Komunikacji i Marketingu',
            3287 =>'Departament Komunikacji i Marketingu',
            4316 =>'Departament Komunikacji i Marketingu',
            4317 =>'Departament Komunikacji i Marketingu',
            4318 =>'Departament Komunikacji i Marketingu',
            4064 =>'Departament Komunikacji i Marketingu',
            4065 =>'Departament Komunikacji i Marketingu',
            4321 =>'Departament Komunikacji i Marketingu',
            4066 =>'Departament Komunikacji i Marketingu',
            4322 =>'Departament Komunikacji i Marketingu',
            4067 =>'Departament Komunikacji i Marketingu',
            4068 =>'Departament Komunikacji i Marketingu',
            4346 =>'Departament Komunikacji i Marketingu',
            3323 =>'Departament Komunikacji i Marketingu',
            3326 =>'Departament Komunikacji i Marketingu',
            4371 =>'Biuro Prawne',
            4372 =>'Biuro Prawne',
            4489 =>'Biuro Prawne',
            4280 =>'Biuro Prawne',
            4373 =>'Biuro Prezesa',
            3888 =>'Departament Usług Proinnowacyjnych',
            4108 =>'Departament Analiz i Strategii',
            4109 =>'Departament Analiz i Strategii',
            3391 =>'Departament Usług Proinnowacyjnych',
            3936 =>'Departament  Usług Rozwojowych',
            4471 =>'Biuro Audytu Wewnętrznego',
            4430 =>'Departament Analiz i Strategii',
            4446 =>'Departament Analiz i Strategii',
            3896 =>'Departament Analiz i Strategii',
            3736 =>'Departament Analiz i Strategii',
            4100 =>'Departament Analiz i Strategii',
            4101 =>'Departament Analiz i Strategii',
            4102 =>'Departament Analiz i Strategii',
            4104 =>'Departament Analiz i Strategii',
            4106 =>'Departament Analiz i Strategii',
            4107 =>'Departament Analiz i Strategii',
            3619 =>'Departament Analiz i Strategii',
            3621 =>'Departament Analiz i Strategii',
            3374 =>'Departament Analiz i Strategii',
            3375 =>'Departament Analiz i Strategii',
            3376 =>'Departament Analiz i Strategii',
            3380 =>'Departament Analiz i Strategii',
            3381 =>'Departament Analiz i Strategii',
            3382 =>'Departament Analiz i Strategii',
            3899 =>'Departament Analiz i Strategii',
            3901 =>'Departament Analiz i Strategii',
            3902 =>'Departament Analiz i Strategii',
            4179 =>'Departament Analiz i Strategii',
            4180 =>'Departament Analiz i Strategii',
            4181 =>'Departament Analiz i Strategii',
            4183 =>'Departament Analiz i Strategii',
            3688 =>'Departament Analiz i Strategii',
            3707 =>'Departament Analiz i Strategii',
            3728 =>'Departament Analiz i Strategii',
            3729 =>'Departament Analiz i Strategii',
            3267 =>'Departament Analiz i Strategii',
            3532 =>'Departament Analiz i Strategii',
            3533 =>'Departament Analiz i Strategii',
            4415 =>'Biuro Informatyki',
            4449 =>'Biuro Informatyki',
            4486 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4487 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4119 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4120 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4121 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4122 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4123 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4124 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4125 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4126 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4127 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4128 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4129 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3627 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3387 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3388 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3916 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4209 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4210 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4211 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3712 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3713 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3512 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3515 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4288 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4289 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4291 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4292 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4293 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4294 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4295 =>'Departament Internacjonalizacji Przedsiębiorstw',
            4296 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3396 =>'Departament Internacjonalizacji Przedsiębiorstw',
            3708 =>'Departament  Usług Rozwojowych',
            3453 =>'Departament  Usług Rozwojowych',
            3709 =>'Departament  Usług Rozwojowych',
            3459 =>'Departament  Usług Rozwojowych',
            4406 =>'Departament  Usług Rozwojowych',
            4407 =>'Departament  Usług Rozwojowych',
            4408 =>'Departament  Usług Rozwojowych',
            4409 =>'Departament  Usług Rozwojowych',
            4410 =>'Departament  Usług Rozwojowych',
            4411 =>'Departament  Usług Rozwojowych',
            4434 =>'Departament  Usług Rozwojowych',
            4435 =>'Departament  Usług Rozwojowych',
            4436 =>'Departament  Usług Rozwojowych',
            4437 =>'Departament  Usług Rozwojowych',
            4438 =>'Departament  Usług Rozwojowych',
            4439 =>'Departament  Usług Rozwojowych',
            4440 =>'Departament  Usług Rozwojowych',
            4441 =>'Departament  Usług Rozwojowych',
            4451 =>'Departament  Usług Rozwojowych',
            3363 =>'Departament  Usług Rozwojowych',
            3451 =>'Departament  Usług Rozwojowych',
            3698 =>'Departament  Usług Rozwojowych',
            4145 =>'Departament  Usług Rozwojowych',
            4146 =>'Departament  Usług Rozwojowych',
            4147 =>'Departament  Usług Rozwojowych',
            4148 =>'Departament  Usług Rozwojowych',
            4149 =>'Departament  Usług Rozwojowych',
            4150 =>'Departament  Usług Rozwojowych',
            4151 =>'Departament  Usług Rozwojowych',
            4152 =>'Departament  Usług Rozwojowych',
            4153 =>'Departament  Usług Rozwojowych',
            4154 =>'Departament  Usług Rozwojowych',
            4155 =>'Departament  Usług Rozwojowych',
            3646 =>'Departament  Usług Rozwojowych',
            3647 =>'Departament  Usług Rozwojowych',
            3410 =>'Departament  Usług Rozwojowych',
            3417 =>'Departament  Usług Rozwojowych',
            3421 =>'Departament  Usług Rozwojowych',
            3422 =>'Departament  Usług Rozwojowych',
            3424 =>'Departament  Usług Rozwojowych',
            3425 =>'Departament  Usług Rozwojowych',
            3426 =>'Departament  Usług Rozwojowych',
            3938 =>'Departament  Usług Rozwojowych',
            3949 =>'Departament  Usług Rozwojowych',
            3950 =>'Departament  Usług Rozwojowych',
            3720 =>'Departament  Usług Rozwojowych',
            3721 =>'Departament  Usług Rozwojowych',
            3722 =>'Departament  Usług Rozwojowych',
            3752 =>'Departament  Usług Rozwojowych',
            4374 =>'Biuro Zarządzania Jakością',
            4375 =>'Biuro Zarządzania Jakością',
            3278 =>'Departament  Usług Rozwojowych',
        ];
        
        $em = $this->getDoctrine()->getManager();
        $wynik = [];
        foreach($zamienBiura as $id => $biuro){
            $error = "";
            $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($id);
            
            if($biuro == "???"){
                $wynik[] = [
                        'zasob' => $id,
                        'biuro' => $biuro,    
                        'error' => "deaktywowano"
                    ];
                $zasob->setPublished(0);
            }else{
            
                $departament = $em->getRepository('ParpMainBundle:Departament')->findOneByName($biuro);
                if($zasob == null && $departament == null){
                    $error = "brak zasobu o id $id oraz depatramentu $biuro";
                }
                elseif($zasob == null){
                    $error = "brak zasobu o id $id";
                }elseif($departament == null){
                    $error = "brak depatramentu $biuro";
                }else{
                    //OK
                    if($zasob->getKomorkaOrgazniacyjna() != $departament->getName()){
                        $error = "Zmiana biura z ".$zasob->getKomorkaOrgazniacyjna()." na $biuro ";
                        $zasob->setBiuro($departament->getName());
                        $zasob->setKomorkaOrgazniacyjna($departament->getName());
                    }else{
                        $error = "biuro bez zmian $id $biuro";
                    }
                }
                
                if($error != ""){
                    $wynik[] = [
                        'zasob' => $id,
                        'biuro' => $biuro,    
                        'error' => $error
                    ];
                }
                
            }
            
        }
        //$em->flush();
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wynik]);
    }
    
    /**
     * @Route("/listLogs/{file}", name="listLogs", defaults={"file" : ""})
     * @Template()
     */
    public function listLogsAction($file = "")
    {
        if($file == ""){
            //show list
            $finder = new Finder();
            $finder->files()->in(__DIR__."/../../../../work/logs/");
            
            $links = [];
            
            foreach ($finder as $file) {
                $links[] = '<li class="list-group-item"><a href="'.$this->generateUrl("listLogs", ["file" => $file->getRelativePathname()]).'" class="btn btn-primary">'.$file->getRelativePathname().'</a></li>';                
            }
            sort($links);
            return new Response('<html><body><ul class="list-group">'.implode("", $links).'</li></body>');
        }else{
            
            //download file
            $file = __DIR__."/../../../../work/logs/".$file;
            $fileStr = file_get_contents($file);
            $response = new Response($fileStr);
            //die($response);
            //$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            
            return $response;

        } 
    }
    
    
    /**
     * @Route("/getAllZablokowaniFromAD", name="getAllZablokowaniFromAD", defaults={})
     * @Template()
     */
    public function getAllZablokowaniFromADAction(){
        $ldap = $this->get('ldap_service');
        //$us = $ldap->getAllFromADIntW("zablokowane", true);
        $us = $ldap->getAllFromADIntW("nieobecni", true);
        die();
    }
    
    
    
    
    /**
     * @Route("/checkWnioskiDomenowe", name="checkWnioskiDomenowe", defaults={})
     * @Template()
     */
    public function checkWnioskiDomenoweAction(){
        
        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find(11);
        
        if($wniosek->getWniosekDomenowy()){
            die("domenowy'");
        }else{
            die("zwykly'");
            
        }
        
    }
    
    /**
     * @Route("/fixDIP_DPI", name="getAllZablokowaniFrofixDIP_DPImAD", defaults={})
     * @Template()
     */
    public function fixDIP_DPIAction(){
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        //$us = $ldap->getAllFromADIntW("zablokowane", true);
        $us = $ldap->getAllFromAD();
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $dane = [];
        foreach($us as $u){
            $dep =  $em->getRepository('ParpMainBundle:Departament')->findOneBy(['shortname' => $u['description'], 'nowaStruktura' => 1]);
            if($dep == null || trim($dep->getName()) != trim($u['department'])){
                $u['error'] = $dep == null ? "Brak depu!!!" : "Skrot i nazwa depu sie nie zgadzaja";
                unset($u['thumbnailphoto']);
                unset($u['memberOf']);
                $u['desc1'] = ".".trim($u['department']).".";
                $u['desc2'] = ".".($dep ? trim($dep->getName()) : "brak").".";
                $u['skrot1'] = ".".($dep ? trim($u['description']) : "brak").".";
                $u['skrot2'] = ".".($dep ? trim($dep->getShortname()) : "brak").".";
                $dane[] = $u;
                if($u['samaccountname'] == 'Edyta_Dominiak'){
                    //poprawioamy na DIP
                    $entry = ["description" => "DRU", "extensionAttribute14" => "DRU"];
                    
                    //$ldapAdmin->ldap_modify($ldapconn, $u['distinguishedname'], $entry);
                    //echo "!!!wrzucam do ad!!!";
                }
            }
        }
        
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $dane]);
    }
    
    /**
     * @Route("/primaryAddressChange", name="primaryAddressChange", defaults={})
     * @Template()
     */
    public function primaryAddressChangeAction(){
        
        $sam = "kamil_jakacki";
        $ldap = $this->get('ldap_admin_service');
        $ldap->changePrimaryEmail($sam, "kacy@parp.gov.pl");
        
    }
    
    
    /**
     * @Route("/getAllWithAttribute/{attr}/{attr2}", name="getAllWithAttribute", defaults={"attr2" : ""})
     * @Template()
     */
    public function getAllWithAttributeAction($attr, $attr2 = ""){
        $ldap = $this->get('ldap_service');
        $us = $ldap->getAllFromAD();
        $ret = [];
        $data = [];
        foreach($us as $u){
            $k = $u[$attr].($attr2 == "" ? "" : $u[$attr2]);
            $ret[$k]['value'] = $u[$attr];
            if($attr2 != ""){
                
                $ret[$k]['value2'] = $u[$attr2];
            }
            $ret[$k]['users'][] = $u['samaccountname'];
        }
        foreach($ret as $k => $v){
            $data[] = $v;
        }
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $data]);
    }
    /**
     * @Route("/getAllAll", name="getAllAll")
     * @Template()
     */
    public function getAllAllAction(){
        $ldap = $this->get('ldap_service');
        $us = $ldap->getAllFromADIntW("wszyscyWszyscy");
        die(count($us).".");
        
    }
    
    
    /**
     * @Route("/testNullDivision", name="testNullDivision", defaults={})
     * @Template()
     */
    public function testNullDivisionAction(){
        
        $sam = "kamil_jakacki";
        
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        
        
        $user = $ldap->getUserFromAD("kamil_jakacki");
        
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        
        $sekcja = "Sekcja Rozwoju Oprogramowania";
        $ldapAdmin->ldap_modify($ldapconn, $user[0]['distinguishedname'], ['initials' => "KJ", 'info' => $sekcja]);
        
    }
    
    /**
     * @Route("/getPrzelozeni", name="getPrzelozeni")
     * @Template()
     */
    public function getPrzelozeniAction(){
        $ldap = $this->get('ldap_service');
        $us = $ldap->getPrzelozeni();
        var_dump($us);
        die(count($us).".");
        
    }
    
    
    /**
     * @Route("/testMonolog", name="testMonolog")
     * @Template()
     */
    public function testMonologAction(){
        $logger = $this->get('logger');
        $logger->critical('I left the oven on!', array(
            'cause' => 'in_hurry',
        ));
        die('aaa');
    }
    
    
    
    /**
     * @Route("/excelExport", name="excelExport")
     * @Template()
     */
    public function excelExportAction(){
        
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:        
        $adusers = $ldap->getAllFromAD();
        $dajKolumny = ["name", "department", "description", "info", "division", "manager"];
        $nazwyKolumn = ["name" => "Pracownik", "department" => "Departament", "description" => "Departament skrót", "info" => "Sekcja", "division" => "Sekcja skrót", "manager" => "Przełożony"];
        $kolumny = [];
        $data = [];
        $data[] = $nazwyKolumn;
        foreach($adusers as $u){
            $d = [];
            foreach($dajKolumny as $k1 => $k){
                $v = $u[$k];
                if($k == "manager"){
                    $v = substr($u['manager'], 0, stripos($u['manager'], ','));
                    $v = str_replace("CN=", "", $v);
                }
                $d[] = $v;
            }
            $data[] = $d;
        }
        //var_dump($data); die();
        
        $phpExcelObject = new \PHPExcel();
        $sheet = $data;
        $phpExcelObject->setActiveSheetIndex(0);

        $phpExcelObject->getActiveSheet()->fromArray($sheet, null, 'A1');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="pracownicy.xls"');
        header('Cache-Control: max-age=0');
        
          // Do your stuff here
          $writer = \PHPExcel_IOFactory::createWriter($phpExcelObject, 'Excel5');
        
        $writer->save('php://output');
        die();
    }
    
    
    /**
     * @Route("/testPublishErrors", name="testPublishErrors")
     * @Template()
     */
    public function testPublishErrorsAction(){
        
        $ldap = $this->get('ldap_service');
        $us = $ldap->getUserFromAD('kamil_jakacki');
        
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        
        $g = "SGG-DIP-Wewn-Wsp-RW";
        $grupa = $ldapAdmin->getGrupa($g);
        
        $ldapAdmin->ldap_mod_del($ldapconn, $grupa['distinguishedname'], ['member' => $us[0]['distinguishedname']]);
        
        var_dump($ldapAdmin->lastConnectionErrors);
        
        
    }
    public function writeln($msg){
        echo "$msg\r\n<br>";    
    }
    
    
    
    /**
     * @Route("/sendMail", name="sendMail")
     * @Template()
     */
    public function sendMailAction($samaccountname){
        $samaccountname = "test_test_wcale_nie_utworzony";
        $mails = ["kamil_jakacki@parp.gov.pl", "marcin_lipinski@parp.gov.pl"];
        $view = "Dnia ".date("Y-m-d")." został utworzony nowy użytkownik '".$samaccountname."', utwórz mu pocztę pliz :)";
        $message = \Swift_Message::newInstance()
                ->setSubject('Nowy użytkownik w AkD')
                ->setFrom('intranet@parp.gov.pl')
                //->setFrom("kamikacy@gmail.com")
                ->setTo($mails)
                ->setBody($view)
                ->setContentType("text/html");

        //var_dump($view);
        $this->container->get('mailer')->send($message);
    }
    
    
    /**
     * @Route("/synchroDyrektorow", name="synchroDyrektorow")
     * @Template()
     */
    public function synchroDyrektorowAction(){
        $stanowiska = ["dyrektor", "p.o. dyrektora", "rzecznik beneficjenta parp, dyrektor", "główny księgowy, dyrektor"];
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        
        $wyniki = [];
        $zmian = 0;
        $em = $this->getDoctrine()->getManager();
        foreach($ADUsers as $u){
            if(in_array($u['title'], $stanowiska)){
                $departament = $em->getRepository("ParpMainBundle:Departament")->findOneByShortname($u['description']);
                $dyrektorZmiana = $departament->getDyrektor() != $u['samaccountname'];
                $wyniki[$u['description']] = [
                    'description' => $u['description'],
                    'department' => $u['department'],
                    'title' => $u['title'],
                    'samaccountname' => $u['samaccountname'],
                    'departament' => $departament->getId(),
                    'dyrektorZmiana' => $dyrektorZmiana
                ];
                if($dyrektorZmiana){
                    $zmian++;
                    $departament->setDyrektor($u['samaccountname']);
                    $departament->setDyrektorDN($u['distinguishedname']);
                    $zasoby = $em->getRepository("ParpMainBundle:Zasoby")->findByBiuro($departament->getName());
                    //die(count($zasoby).".".$departament->getName());
                    
                }
            }
        }
        if($zmian > 0){
            $em->flush();
        }
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => [$wyniki]]);
        //var_dump($wyniki); die();
    }
    
    
    /**
     * @Route("/uprawnieniaLsiAudyt", name="uprawnieniaLsiAudyt")
     * @Template()
     */
    public function uprawnieniaLsiAudytAction(){
        //generuje liste sqli z aktualnymi uprawnieniami do lsi
        $em = $this->getDoctrine()->getManager();
        $userZasoby = $em
            ->getRepository('ParpMainBundle:UserZasoby')
            ->createQueryBuilder('uz')
            //->join('e.idRelatedEntity', 'r')
            ->where('uz.zasobId = 4420')
            ->andWhere('uz.aktywneOd <= :now and (uz.aktywneDo is null or uz.aktywneDo >= :now)')
            ->setParameter('now', date("Y-m-d H:i:s"))            
            ->getQuery()
            ->getResult();
        $sqls = [];
        foreach($userZasoby as $uz){
            $nSqls = $uz->getLsiSql();
            $sqls = array_merge($sqls, $nSqls);
        }    
        //var_dump($sqls);
        //die(count($userZasoby).".");
        $msg = "\n\n-----------------------------------------\n\n".implode(";\n", $sqls);
        $msg = nl2br($msg);
        die($msg);
        
    }
    
    
    /**
     * @Route("/poprawAtrybut", name="poprawAtrybut")
     * @Template()
     */
    public function poprawAtrybutAction(){
        
        
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        
        $atrybut = "info";
        $wartosci = ["Nd", "ND", "N/d", "n/d"];
        $nowaWartosc = [];
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $wynik = [];
        foreach($ADUsers as $u){
            if(in_array(trim($u[$atrybut]), $wartosci)){
                $wynik[] = ['samaccountname' => $u['samaccountname'], $atrybut => $u[$atrybut]];
                //$ldapAdmin->ldap_modify($ldapconn, $u['distinguishedname'], [$atrybut => $nowaWartosc]);
                
            }
        }
        //var_dump($wynik);
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wynik]);
    }
    
}    