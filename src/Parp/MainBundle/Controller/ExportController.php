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
 * Export controller.
 *
 * @Route("/export")
 */
class ExportController extends Controller
{
    
    
    /**
     * @Route("/zasoby/{aktywne}", name="zasobyExcelExport")
     */
    public function zasobyExcelExportAction($aktywne){
        
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $mapaOsob = [];
        foreach($ADUsers as $u){
            $mapaOsob[$u['samaccountname']] = $u['name'];
        }
        
        
        $em = $this->getDoctrine()->getManager();
        $zasoby = $em->getRepository("ParpMainBundle:Zasoby")->findByPublished($aktywne);
        $data =[
            [
                'Id',
                'Nazwa',
                'Opis',
                'Właściciel',
                'Administrator',
            ]
        ];
        
        foreach($zasoby as $zasob){
            $data[] = [
                'id' => $zasob->getId(),
                'nazwa' => $zasob->getNazwa(),
                'opis' => $zasob->getOpis(),
                'wlasciciel' => isset($mapaOsob[$zasob->getWlascicielZasobu()]) ? $mapaOsob[$zasob->getWlascicielZasobu()] : $zasob->getWlascicielZasobu(),
                'administrator' => isset($mapaOsob[$zasob->getAdministratorZasobu()]) ? $mapaOsob[$zasob->getAdministratorZasobu()] : $zasob->getAdministratorZasobu(),
                'aktywny' => $zasob->getPublished(),   
            ];
        }
        
        //var_dump($data); die();
        
        $phpExcelObject = new \PHPExcel();
        $sheet = $data;
        $phpExcelObject->setActiveSheetIndex(0);

        $phpExcelObject->getActiveSheet()->fromArray($sheet, null, 'A1');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="zasoby.xls"');
        header('Cache-Control: max-age=0');
        
          // Do your stuff here
          $writer = \PHPExcel_IOFactory::createWriter($phpExcelObject, 'Excel5');
        
        $writer->save('php://output');
        die();
    }
}