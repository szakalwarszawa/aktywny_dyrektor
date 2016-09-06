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
     * @Route("/lista/{ktorzy}", name="lista", defaults={"ktorzy" : "zablokowane"})
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
        $grid = $ctrl->getUserGrid($this->get('grid'), $ADUsers);        

        if($ktorzy == "zablokowane"){
            // Edycja konta
            $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Odblokuj', 'unblock');
            $rowAction2->setColumn('akcje');
            $rowAction2->setRouteParameters(
                    array('samaccountname', 'ktorzy' => $ktorzy)
            );
            $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
            $grid->addRowAction($rowAction2);
        }
            
        
        
        //print_r($users);
        //die();
        
        return $grid->getGridResponse(['ktorzy' => $ktorzy]);
    }
    
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/unblock/{ktorzy}/{samaccountname}", name="unblock")
     * @Template()
     */
    public function unblockAction($ktorzy, $samaccountname)
    {
        
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname, null, null, $ktorzy);
        $daneRekord = $this->getDoctrine()->getManager()->getRepository("ParpMainBundle:DaneRekord")->findOneByLogin($samaccountname);
        $ctrl = new DefaultController();
        $form = $ctrl->createUserEditForm($this, $ADUser[0]);
        $dane = [
            'samaccountname' => $samaccountname,
            'daneRekord' => $daneRekord,
            'user' => (count($ADUser) > 0 ? $ADUser[0] : null),
            'form' => $form->createView()
        ];
        //print_r($dane); die();
        
        return $dane;
    }
}