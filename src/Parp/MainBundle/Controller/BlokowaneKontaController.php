<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;

use Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Parp\MainBundle\Form\WniosekNadanieOdebranieZasobowType;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Parp\MainBundle\Exception\SecurityTestException;
use Parp\MainBundle\Entity\Entry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use APY\DataGridBundle\Grid\Column\TextColumn;
/**
 * BlokowaneKontaController .
 *
 * @Route("/blokowanekonta")
 */
class BlokowaneKontaController extends Controller
{
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/lista/{ktorzy}", name="lista_oblokowania", defaults={"ktorzy" : "zablokowane"})
     * @Security("has_role('PARP_ADMIN', 'PARP_BZK_1', 'PARP_BZK_2')")
     * @Template()
     */
    public function listaAction(Request $request, $ktorzy = "zablokowane" /* nieobecni */)
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromADIntW($ktorzy);
        
        if(count($ADUsers) == 0){
            return $this->render('ParpMainBundle:Default:NoData.html.twig');
        }
        //echo "<pre>"; print_r($ADUsers); die();
        $ctrl = new DefaultController();
        $grid = $ctrl->getUserGrid($this->get('grid'), $ADUsers, $ktorzy, $this->getUser()->getRoles());        

        //if($ktorzy == "zablokowane"){
            // Edycja konta
            $rowAction = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Odblokuj', 'unblock_user');
            $rowAction->setColumn('akcje');
            $rowAction->setRouteParameters(
                    array('samaccountname', 'ktorzy' => $ktorzy)
            );
            $rowAction->addAttribute('class', 'btn btn-success btn-xs');
    
            $grid->addRowAction($rowAction);
        //}
        $grid->isReadyForRedirect();
        //var_dump($rowAction2);
        
        //print_r($users);
        //die();
        
        return $grid->getGridResponse(['ktorzy' => $ktorzy]);
    }
    
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/unblock/{ktorzy}/{samaccountname}", name="unblock_user")
     * @Template()
     * @Security("has_role('PARP_ADMIN', 'PARP_BZK_1', 'PARP_BZK_2')")
     */
    public function unblockAction(Request $request, $ktorzy, $samaccountname)
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname, null, null, $ktorzy);
        $daneRekord = $em->getRepository("ParpMainBundle:DaneRekord")->findOneByLogin($samaccountname);
        $ctrl = new DefaultController();
        $form = $ctrl->createUserEditForm($this, $ADUser[0]);
        $departamentRekord = "";
        if($daneRekord){
            $departamentRekord = $this->getDoctrine()->getManager()->getRepository("ParpMainBundle:Departament")->findOneByNameInRekord($daneRekord->getDepartament());
        }
        $form->handleRequest($request);
        if ($request->getMethod() == "POST") {
            $data = $request->request->get('form'); 
            $ctrl = new DefaultController();   
            
            //var_dump($data);
            $entry = new Entry();
            $entry->setSamaccountname($samaccountname);
            $ctrl->parseUserFormData($data, $entry);
            
            //var_dump($entry); die();
            //dodac flage ze odblokowanie
            //dodac metode w ldapAdmin ktora przeniesie z odblokowanych
            $entry->setActivateDeactivated(true);
            $entry->setIsDisabled(0);
            $entry->setFromWhen(new \Datetime());
            
            //$aduser = $this->get('ldap_service')->getUserFromAD(null, "", null, $ktorzy);
            
            $cn =  $daneRekord->getNazwisko(). " " . $daneRekord->getImie();
            $entry->setCn($cn);
            $entry->setDistinguishedname($ADUser[0]['distinguishedname']);
            
            $entry->setCreatedBy($this->getUser()->getUsername());
            $em->persist($entry);
            $em->flush();
            
            return $this->redirect($this->generateUrl('main'));
        }
        
        $dane = [
            'samaccountname' => $samaccountname,
            'daneRekord' => $daneRekord,
            'user' => (count($ADUser) > 0 ? $ADUser[0] : null),
            'form' => $form->createView(),
            'departamentRekord' => $departamentRekord
        ];
        //print_r($dane); die();
        
        return $dane;
    }
    
    
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/kontaDisabledPrzenies", name="kontaDisabledPrzenies")
     * @Security("has_role('PARP_ADMIN', 'PARP_BZK_1', 'PARP_BZK_2')")
     * @Template()
     */
    public function kontaDisabledPrzeniesAction(Request $request)
    {
        $ldap = $this->get('ldap_service');
        
        $disabled = $ldap->getAllDisabled();
        
        for($i = 0; $i < count($disabled); $i++){
            $d = $disabled[$i];
            $name = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($d['name']);
            $rekordDane = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:DaneRekord')->findOneBy(
                [
                    'nazwisko' => $name[0],
                    'imie' => $name[1],
                ]
            );
            $d['daneRekord'] = (array)$rekordDane;
            unset($d['daneRekord']['entries']);
            $disabled[$i] = $d;
            //var_dump($d);
        }

        
        
        $ctrl = new DefaultController();
        $grid = $ctrl->getUserGrid($this->get('grid'), $disabled, "nieobecni", $this->getUser()->getRoles());        

        $grid->hideColumns(['thumbnailphoto', 'daneRekord', 'akcje']);
        
        $akcje = new TextColumn(array('id' => 'akcje', 'title' => 'Przenieś do', 'source' => false, 'filterable' => false, 'sortable' => false));

        $grid->addColumn($akcje);
        
        $ktorzy = "disabled";
        
        if ($request->getMethod() == "POST") {
            $postData = $request->request->all();
            var_dump($postData);
            die("mam post");    
        }
        
        /*
            // Edycja konta
            $rowAction = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Odblokuj', 'unblock_user');
            $rowAction->setColumn('akcje');
            $rowAction->setRouteParameters(
                    array('samaccountname', 'ktorzy' => $ktorzy)
            );
            $rowAction->addAttribute('class', 'btn btn-success btn-xs');
    
            $grid->addRowAction($rowAction);
        */
        $grid->isReadyForRedirect();
        //var_dump($rowAction2);
        
        //print_r($users);
        //die();
        $keys = array_keys($disabled[0]);
        //unset($keys[count($keys) - 1]);
        //var_dump($keys, $disabled[0]); //die();
        return $grid->getGridResponse(['ktorzy' => $ktorzy, 'polaAD' => $keys]); 
    }
    
}