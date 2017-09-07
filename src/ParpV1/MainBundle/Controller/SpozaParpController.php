<?php

namespace ParpV1\MainBundle\Controller;

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
 * @Route("/spozaparp")
 */
class SpozaParpController extends Controller
{
    /**
     * Lists all DaneRekord entities.
     *
     * @Route("/index", name="spozaparp")
     * @Template()
     */
    public function indexAction()
    {
        ;
        
        $em = $this->getDoctrine()->getManager();
        $wnioski = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->findBy(
            [
                'pracownikSpozaParp' => 1
            ]
        );
        
        $wynik = [];
        foreach ($wnioski as $wniosek) {
            foreach ($wniosek->getUserZasoby() as $uz) {
                $id = $uz->getSamaccountname()." ".$wniosek->getManagerSpozaParp();
                $wynik[$id] = [
                    'osoba' => $uz->getSamaccountname(),
                    'manager' => $wniosek->getManagerSpozaParp(),
                    'departament' => $wniosek->getWniosek()->getJednostkaOrganizacyjna()
                ];
            }
        }
    
        echo "<pre>";
        print_r($wynik);
        die();
    
        $source = new Vector($ADUsers);
    
        $grid = $this->get('grid');
        $grid->setSource($source);
    
        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);
    
        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);
    
        // Edycja konta
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'danerekord_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'danerekord_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
}
