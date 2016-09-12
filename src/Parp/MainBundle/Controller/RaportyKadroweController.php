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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Parp\MainBundle\Exception\SecurityTestException;

/**
 * RaportyKadrowe controller.
 *
 * @Route("/RaportyKadrowe")
 */
class RaportyKadroweController extends Controller
{
    protected $maxMiesiac = 12;
    protected $debug = 0;
    protected $miesiace = [
        '1' => 'Styczeń',
        '2' => 'Luty',
        '3' => 'Marzec',
        '4' => 'Kwiecień',
        '5' => 'Maj',
        '6' => 'Czerwiec',
        '7' => 'Lipiec',
        '8' => 'Sierpień',
        '9' => 'Wrzesień',
        '10' => 'Październik',
        '11' => 'Listopad',
        '12' => 'Grudzień',
    ];
    /**
     *
     * @Route("/generujRaport", name="raportyKadrowe")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        if($this->getUser()->getUsername() != "kamil_jakacki"){
            throw new SecurityTestException('Nie masz dostępu do tej części aplikacji', 999);
        }
        $lata = [];
        for($i = date("Y"); $i > 2003 ; $i--){
            $lata[$i] = $i;
        }
        $builder = $this->createFormBuilder()
            ->add('rok', 'choice', array(
                'required' => true,
                'label' => 'Wybierz rok do raportu',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'choices' => $lata,
                'attr' => array(
                    'class' => 'form-control',
                ),
            ));
        $builder->add('zapisz', 'submit', array(
            'attr' => array(
                'class' => 'btn btn-success col-sm-12',
            ),
        ));
        $form = $builder->setMethod('POST')->getForm();
        
        
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();
            $rok = $ndata['rok'];
            
            $data = $this->getRaportKadrowyData($rok);
            //return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $data]);   
            return $this->generateExcel($data, $rok);
        }
        
        return [
            'form' => $form->createView()    
        ];
    }
    
    protected function getRaportKadrowyData($rok){
        $data = [
            'headers' => ['programowe' => [], 'placowe' => []],
            'data' => []
        ];
        $c = new ImportRekordDaneController();
        $c->setContainer($this->container);
        
        for($i = 1; $i <= $this->maxMiesiac; $i++){
            //podzial na programy operacyjne
            $sql = $this->getSqlDoRaportuKadrowegoProgramyOperacyjne($rok, $i);
            //die($sql);
            $dane = $c->executeQuery($sql);
            //echo "<pre>"; print_r($rok); print_r($i); print_r($dane);  //die();
            $lastId = "";
            foreach($dane as $d){
                $program = $this->parseValue($d['DZIALANIE']);
                if(!isset($data['headers'][$program])){
                    $data['headers']['programowe'][$program." "] = $program;
                }
                if($lastId == "" || $lastId != $d['ID']){
                    if($lastId != ""){
                        $data['data'][$i][$newdata['Id']] = $newdata;
                    }
                    $newdata = [
                        'Id' => $this->parseValue($d['ID']),
                        'Nazwisko' => $this->parseValue($d['NAZWISKO']),
                        'Imie' => $this->parseValue($d['IMIE']),
                        'Deprtament' => '',
                        'DeprtamentKod' => '',
                        'Stanowisko' => '',
                        //powyzsze pozniejszy sql ogarnia
                        'kolumny' => []
                    ];
                }
                $newdata['kolumny'][$program." "] = $this->parseValue($d['KWOTA']);
                ///echo "<pre>";print_r($newdata);
                $lastId = $d['ID'];
            }
            if($newdata){
                $data['data'][$i][$newdata['Id']] = $newdata;
            }
            //echo "<pre>"; print_r($rok); print_r($i); /* print_r($data); */ print_r($data); die();
            //teraz normalne skladniki placowe 
            $sql = $this->getSqlDoRaportuKadrowegoSkladnikiPlacowe($rok, $i);
            //die($sql);
            $dane = $c->executeQuery($sql);
            //print_r($dane); die();
            $lastId = "";
            foreach($dane as $d){
                if(!isset($data['headers'][$d['RODZAJ']])){
                    $data['headers']['placowe'][$this->parseValue($d['RODZAJ'])." "] = "[".$d['RODZAJ']."] ".$this->parseValue($d['OPIS']);
                }
                if($lastId == "" || $lastId != $d['ID']){
                    if($lastId != ""){
                        if(!isset($data['data'][$i][$newdata['Id']])){
                            $data['data'][$i][$newdata['Id']] = $newdata;
                        }else{
                            //laczymy arraye kolumn
                            $data['data'][$i][$newdata['Id']]['kolumny'] = array_merge($data['data'][$i][$newdata['Id']]['kolumny'], $newdata['kolumny']);
                            $data['data'][$i][$newdata['Id']]['Departament'] = $this->parseValue($d['SYMBOL']);
                        }
                    }
                    $newdata = [
                        'Id' => $this->parseValue($d['ID']),
                        'Nazwisko' => $this->parseValue($d['NAZWISKO']),
                        'Imie' => $this->parseValue($d['IMIE']),
                        'Departament' => $this->parseValue($d['SYMBOL']),
                        'DepartamentKod' => $this->parseValue($d['SYMBOL']),
                        'Stanowisko' => '',
                        'kolumny' => []
                    ];
                }
                $newdata['kolumny'][$this->parseValue($d['RODZAJ'])." "] = $this->parseValue($d['KWOTA']);
                $lastId = $d['ID'];
            }
            if(!isset($data['data'][$i][$newdata['Id']])){
                $data['data'][$i][$newdata['Id']] = $newdata;
            }else{
                //laczymy arraye kolumn
                $data['data'][$i][$newdata['Id']]['kolumny'] = array_merge($data['data'][$i][$newdata['Id']]['kolumny'], $newdata['kolumny']);
                $data['data'][$i][$newdata['Id']]['Departament'] = $this->parseValue($d['SYMBOL']);
            }
            
            //dodaje departamenty i stanowiska
            
            $sql = $this->getSqlDepartamentStanowisko($rok, $i);
            
            //die($sql);
            $dane = $c->executeQuery($sql);
            foreach($dane as $d){
                if(isset($data['data'][$i][$d['SYMBOL']])){
                    $data['data'][$i][$d['SYMBOL']]['Departament'] = $this->parseValue($d['DEPARTAMENTNAZWA']);
                    $data['data'][$i][$d['SYMBOL']]['Stanowisko'] = $this->parseValue($d['STANOWISKO']);
                }
            }
        }
        asort($data['headers']);
        if($this->debug){
            echo "<pre>"; print_r($data); die();
        }
        return $data;
    }
    protected function parseValue($v){
        try{
            //return trim($v); //
            //return iconv("WINDOWS-1250", "UTF-8", trim($v));
            return iconv("WINDOWS-1250", "UTF-8", trim($v));
        }catch(\Exception $e){
            die(trim($v)." ".$e->getMessage());
        }
    }
    protected function getSqlDoRaportuKadrowegoSkladnikiPlacowe($rok, $miesiac){
        $sql = 'select  pr.symbol as id, pr.nazwisko, pr.imie, m.kod symbol, p.rodz as rodzaj, s.opis,sum(kwota) kwota, sum(godz)/60 godz 
from p_lp_pla p,
p_listapl l,
p_skladnik s, 
p_lp_prac m,
p_pra_grgus g,
p_pracownik pr
where l.id=p.id and p.rodz=s.rodz  and p.symbol=m.symbol  and l.rok_O = '.$rok.' and l.miesiac_O = '.$miesiac.'   and p.symbol=g.symbol  and m.id=l.id and m.typ=0 and 1=1 and 1=1  and 1=1 and pr.symbol = p.symbol 
and p.rodz not in ( \'742\', \'743\', \'740\', \'744\', \'745 \'\',746\', \'748\', \'725\', \'726\', \'747\',
 \'825\', \'830\', \'856\', \'857\', \'905\', \'907\', \'910\', \'913\', \'914\', \'006\')
group by m.kod,p.rodz,s.opis, pr.nazwisko, pr.imie, pr.symbol

union 

select  pr.symbol as id, pr.nazwisko, pr.imie, m.kod symbol, p.rodz as rodzaj, s.opis,sum(-kwota),sum(m.id-m.id)/60 godz from 
p_lp_pot p,
p_listapl l,
p_skladnik s, 
p_lp_prac m,
p_pra_grgus g , 
p_pracownik pr
where l.id=p.id and p.rodz=s.rodz  and p.symbol=m.symbol  and l.rok_O = '.$rok.' and l.miesiac_O = '.$miesiac.'  and p.symbol=g.symbol  and m.id=l.id and m.typ=0 and 1=1 and 1=1  and 1=1 and pr.symbol = p.symbol
and p.rodz not in ( \'742\', \'743\', \'740\', \'744\', \'745 \'\',746\', \'748\', \'725\', \'726\', \'747\',
 \'825\', \'830\', \'856\', \'857\', \'905\', \'907\', \'910\', \'913\', \'914\', \'006\')
group by m.kod,p.rodz,s.opis, pr.nazwisko, pr.imie, pr.symbol';
        
        return $sql;
    }
    protected function getSqlDoRaportuKadrowegoProgramyOperacyjne($rok, $miesiac){
        $sql = 'select d.symbol as id,d.rodz,d.db, sum(d.kwota) kwota,p.nazwisko,p.imie, f.dzialanie,f.zrodlo_fin,f.wpl_wyd,f.zadanie  
        from 
        p_lp_pla_db d,
        p_listapl l,
        p_pracownik p,
        f_db f 
        where d.id=l.id and d.symbol=p.symbol and f.db=d.db and l.rok_O = '.$rok.' and l.miesiac_O ='.$miesiac.' and d.rodz = \'010\'    
        group by 1,2,3,5,6,7,8,9,10 order by p.nazwisko, p.imie';
        return $sql;
    }
    protected function getSqlDepartamentStanowisko($rok, $miesiac){
        $sql = "SELECT
            p.SYMBOL,
            departament.OPIS as departamentNazwa,
            departament.KOD  departament,
            stanowisko.OPIS stanowisko
            
            from P_PRACOWNIK p
            join PV_MP_PRA mpr on mpr.SYMBOL = p.SYMBOL AND (mpr.DATA_DO is NULL OR mpr.DATA_DO >= '".$rok."-".$miesiac."-01') AND mpr.DATA_OD <=  '".$rok."-".$miesiac."-01'
            join P_MPRACY departament on departament.KOD = mpr.KOD
            JOIN PV_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL AND (stjoin.DATA_DO is NULL OR stjoin.DATA_DO > '".$rok."-".$miesiac."-01') AND stjoin.DATA_OD <= '".$rok."-".$miesiac."-01'
            join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
            group by p.SYMBOL,
            departament.OPIS ,
            departament.KOD ,
            stanowisko.OPIS";
        return $sql;
    }
    
    
    protected function generateExcel($data, $rok){
        $kolumny = ["Lp.", "case ID", "personal ID (rekord ID)", "Imię i nazwisko", "Departament", "Stanowisko"];
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        
        $phpExcelObject->getProperties()->setCreator("Kamil Jakacki")
           ->setLastModifiedBy("Kamil Jakacki")
           ->setTitle("PARP: Raport kadrowy za rok $rok")
           ->setSubject("PARP: Raport kadrowy za rok $rok")
           ->setDescription("PARP: Raport kadrowy za rok $rok.")
           ->setKeywords("PARP: Raport kadrowy za rok $rok")
           ->setCategory("PARP: Raport kadrowy");
        
           
        for($i1 = 1; $i1 <= $this->maxMiesiac; $i1++){    
            // Add new sheet
            $objWorkSheet = $phpExcelObject->createSheet($i1); //Setting index when creating
            $miesiac = $this->miesiace[($i1)];            
            $objWorkSheet->setTitle($miesiac." ".$rok);
            for($i2 = 0; $i2 < count($kolumny); $i2++){
                //echo "dodaje kolumne $i2, 1, ".$kolumny[$i2];
                $objWorkSheet->setCellValueByColumnAndRow($i2, 1, $kolumny[$i2]);
                $kol = $kolumny[$i2];
                switch($kol){
                    case "Lp.":
                        $objWorkSheet->setCellValueByColumnAndRow($i2, 2, $kolumny[$i2]);
                        break;
                    case "case ID":
                        break;
                    case "personal ID (rekord ID)":
                        break;
                    case "Imię i nazwisko":
                        break;
                    case "Departament":
                        break;
                    case "Stanowisko":
                        break;
                }
            }
            foreach($data['headers']['programowe'] as $k => $v){
                //echo "dodaje kolumne $i2, 1, ".$v;
                $objWorkSheet->setCellValueByColumnAndRow($i2, 1, $v);
                $i2++;
            }
            foreach($data['headers']['placowe'] as $k => $v){
                //echo "dodaje kolumne $i2, 1, ".$v;
                $objWorkSheet->setCellValueByColumnAndRow($i2, 1, $v);
                $i2++;
            }
            $rzad = 1;
            foreach($data['data'][$i1] as $osoba){
                for($i2 = 0; $i2 < count($kolumny); $i2++){
                    switch($kolumny[$i2]){
                        case "Lp.":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $rzad);
                            break;
                        case "case ID":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $osoba['Id']);
                            break;
                        case "personal ID (rekord ID)":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $osoba['Id']);
                            break;
                        case "Imię i nazwisko":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $osoba['Nazwisko']." ".$osoba['Imie']);
                            break;
                        case "Departament":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $osoba['Departament']);
                            break;
                        case "Stanowisko":
                            $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $osoba['Stanowisko']);
                            break;
                    }
                }
                foreach($data['headers']['programowe'] as $k => $v){
                    $v = 0;
                    if(isset($osoba['kolumny'][$k])){
                        $v  = $osoba['kolumny'][$k];
                    }
                    $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $v);
                    
                    $i2++;
                }
                foreach($data['headers']['placowe'] as $k => $v){
                    $v = 0;
                    if(isset($osoba['kolumny'][$k])){
                        $v  = $osoba['kolumny'][$k];
                    }
                    $objWorkSheet->setCellValueByColumnAndRow($i2, ($rzad + 1), $v);
                    
                    $i2++;
                }
                
                $rzad++;
            }
        }
       
       
        $objWorkSheet = $phpExcelObject->removeSheetByIndex(0); //Setting index when creating
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);
        
        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'raportKadrowy'.$rok.'.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        
        return $response; 
    }
}