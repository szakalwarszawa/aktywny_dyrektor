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
        $ADUsers = $ldap->getAllFromAD();
        $sqls = [];
        //print_r($ADUsers); die();
        $pomijaj = ["chuck_norris", "kamil_wirtualny", "ndes-user", "teresa_oneill", "aktywny_dyrektor", "marcin_lipinski"];
        foreach($ADUsers as $u){
            $sam = str_replace("'", "", $u['samaccountname']);
            if(!in_array($sam, $pomijaj)){
                $sqls[] = "INSERT INTO `entry` (`department`, `distinguishedname`, `fromWhen`, `isImplemented`, `samaccountname`) VALUES
    ('Biuro Administracji', 'CN=".str_replace("'", "", $u['name']).",OU=".$u['description'].",OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST', '2016-07-07 00:00:00', 0, '".$sam."');";
            }
        }
        
        
        echo implode("\n\n<br><br>", $sqls);
        die();
        die('przeniesWszystkich');
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
        print_r($entities);
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
}    