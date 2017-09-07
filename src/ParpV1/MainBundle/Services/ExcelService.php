<?php

/**
 * Description of ExcelService
 *
 * @author kamil_jakacki
 */

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Entity\UserGrupa;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Parp\MainBundle\Exception\SecurityTestException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExcelService
{
    protected $container;
    public function __construct(Container $container)
    {
        
        $this->container = $container;
    }
    
    public function generateExcel($data)
    {
        $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();
        
        $title = "Raport uprawnien";
        $phpExcelObject->getProperties()->setCreator("Kamil Jakacki")
           ->setLastModifiedBy("Kamil Jakacki")
           ->setTitle($title)
           ->setSubject($title)
           ->setDescription($title)
           ->setKeywords($title)
           ->setCategory($title);
        
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);
        $activesheet = $phpExcelObject->getActiveSheet();
        //tutaj wrzucamy dane do excela
        if (count($data) > 0) {
            $col = 0;
            //kolumny tworzymy
            foreach ($data[0] as $k => $v) {
                $activesheet->setCellValueByColumnAndRow($col++, 1, $k);
            }
            $row = 2;
            foreach ($data as $d) {
                $col = 0;
                foreach ($d as $k => $v) {
                    $activesheet->setCellValueByColumnAndRow($col++, $row, $v);
                }
                $row++;
            }
        }
        
        
        // create the writer
        $writer = $this->container->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $title.'.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
