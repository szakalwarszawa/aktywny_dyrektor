<?php

namespace Parp\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Parp\MainBundle\Entity\Zasoby;

/**
 * Export controller.
 *
 * @Route("/export")
 */
class ExportController extends Controller
{


    /**
     * @Route("/zasoby/{aktywne}", name="zasobyExcelExport")
     * @param $aktywne
     */
    public function zasobyExcelExportAction($aktywne)
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $mapaOsob = [];

        foreach ($ADUsers as $u) {
            $mapaOsob[$u['samaccountname']] = $u['name'];
        }
        
        $manager = $this->getDoctrine()->getManager();

        /** @var Zasoby[] $zasoby */
        $zasoby = $manager->getRepository("ParpMainBundle:Zasoby")->findBy([
            'published' => $aktywne,
        ]);

        $data = [
            [
                'Id',
                'Nazwa',
                'Właściciel',
                'Powiernicy właściciela',
                'Administrator',
                'Użytkownicy',
                'Dane osobowe',
                'Komórka organizacyjna',
                'Miejsce instalacji',
                'Opis',
            ]
        ];
        
        foreach ($zasoby as $zasob) {
            $data[] = [
                'Id' => $zasob->getId(),
                'Nazwa' => $zasob->getNazwa(),
                'Właściciel' => $this->getNames($zasob->getWlascicielZasobu(), $mapaOsob),
                'Powiernicy właściciela' => $this->getNames($zasob->getPowiernicyWlascicielaZasobu(), $mapaOsob),
                'Administrator' => $this->getNames($zasob->getAdministratorZasobu(), $mapaOsob),
                'Użytkownicy' => $this->getNames($zasob->getUzytkownicy(), $mapaOsob),
                'Dane osobowe' => $zasob->getDaneOsobowe(),
                'Komórka organizacyjna' => $zasob->getKomorkaOrgazniacyjna(),
                'Miejsce instalacji' => $zasob->getMiejsceInstalacji(),
                'Opis' =>  $zasob->getOpisZasobu(),
            ];
        }

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

    /**
     * @param $oss
     * @param $mapaOsob
     * @return string
     */
    protected function getNames($oss, $mapaOsob)
    {
        $osoby = [];
        $os = explode(",", $oss);
        foreach ($os as $o) {
            $osoby[] = isset($mapaOsob[$o]) ? $mapaOsob[$o] : $o;
        }
        return implode(", ", $osoby);
    }
}
