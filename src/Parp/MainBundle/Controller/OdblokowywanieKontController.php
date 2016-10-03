<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
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

use Parp\MainBundle\Entity\DaneRekord;
use Parp\MainBundle\Form\DaneRekordType;

/**
 * DaneRekord controller.
 *
 * @Route("/odblokowywanie")
 */
class OdblokowywanieKontController extends Controller
{
    /**
     * Lists all DaneRekord entities.
     *
     * @Route("/index", name="zablokowani")
     * @Template()
     */
    public function indexAction()
    {
        $defaultController = new DefaultController();
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:
        
        $maDostep = 
            in_array("PARP_BZK_1", $this->getUser()->getRoles()) ||
            in_array("PARP_ADMIN", $this->getUser()->getRoles())
        ;
        if(!$maDostep){
            throw new \Parp\MainBundle\Exception\SecurityTestException('Nie masz uprawnień by odblokowywania użytkowników!');                                
        }
        $ADUsersTemp = $ldap->getAllFromAD("zablokowane");
        $ADUsers = array();
        foreach($ADUsersTemp as $u){
                $ADUsers[] = $u;
        }
        
        if(count($ADUsers) == 0){
            return $this->render('ParpMainBundle:Default:NoData.html.twig');
        }    
        $grid = $defaultController->getUserGrid($this->get('grid'), $ADUsers);        

        // Edycja konta
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'userEdit');
        $rowAction2->setColumn('akcje');
        $rowAction2->setRouteParameters(
                array('samaccountname')
        );
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-sitemap"></i> Struktura', 'structure');
        $rowAction3->setColumn('akcje');
        $rowAction3->setRouteParameters(
                array('samaccountname')
        );
        $rowAction3->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction4 = new RowAction('<i class="fa fa-database"></i> Odblokuj', 'unblock');
        $rowAction4->setColumn('akcje');
        $rowAction4->setRouteParameters(
                array('samaccountname')
        );
        $rowAction4->addAttribute('class', 'btn btn-success btn-xs');

//        $grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
        //$grid->addRowAction($rowAction4);
        

        $grid->setLimits(array(20 => '20', 50 => '50', 100 => '100', 500 => '500', 1000 => '1000'));
        

        return $grid->getGridResponse();
        
    }    
    
}