<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Form\EngagementType;
use Parp\MainBundle\Form\UserEngagementType;
use Parp\MainBundle\Services\ParpMailerService;
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
 * @Route("/nieobecnosci")
 */
class NieobecnosciController extends Controller
{
    protected $ileDni = 60;
    /**
     * @Route("/ponadIlesDni", name="ponadIlesDni")
     * @Template()
     */
    public function ponadIlesDniAction()
    {
        
        $sql = $this->getSqlUrlopy();

        $c = new ImportRekordDaneController();
        $c->setContainer($this->container);
        
        /*
        //pobiera dane o urlopach dla ostatnich x dni 
        $daneUrlopy = $this->grupujUrlopyOsob($c->executeQuery($this->getSqlUrlopy()));
        $daneDniWolneDodatkoweUrlopy1 = $this->grupujUrlopyOsob($c->executeQuery($this->getSqlDniWolne(date("Y")-1)));
        $daneDniWolneDodatkoweUrlopy2 = $this->grupujUrlopyOsob($c->executeQuery($this->getSqlDniWolne(date("Y"))));
        //grupuje po osobach, do kazdej dorzucajac weekendy i swieta poprzedniego roku 
        $this->dodajDniWolneIDodatkoweyUrlopy($daneUrlopy, $daneDniWolneDodatkoweUrlopy1);
        //grupuje po osobach, do kazdej dorzucajac weekendy i swieta tego roku 
        $this->dodajDniWolneIDodatkoweyUrlopy($daneUrlopy, $daneDniWolneDodatkoweUrlopy2);
        //sortuje po dacie nieobecnosci (zeby swieta i urlopy sie poukladaly odpowiednio)
        $this->sortujDane($daneUrlopy);
        */
        $daneUrlopy = [
            100 => [
                [
                    'NAZWISKO' => 'ANTONOWICZ',
                    'IMIE' => 'MONIKA',
                    'SYMBOL' => 100,
                    'KOD' => '10',
                    'ODD' => '2017-01-01',
                    'DOD' => '2017-01-01',
                    'OPIS' => '',
                    'RODZAJ' => 'Urlop taryfowy',
                    'GRUPA' => 'UT',
                    'LABEL' => 'W' 
                ],
                [
                    'NAZWISKO' => 'ANTONOWICZ',
                    'IMIE' => 'MONIKA',
                    'SYMBOL' => 100,
                    'KOD' => '10',
                    'ODD' => '2017-01-03 00:00:00',
                    'DOD' => '2017-01-05 00:00:00',
                    'OPIS' => '',
                    'RODZAJ' => 'Urlop taryfowy',
                    'GRUPA' => 'UT',
                    'LABEL' => 'W' 
                ],
                [
                    'NAZWISKO' => 'ANTONOWICZ',
                    'IMIE' => 'MONIKA',
                    'SYMBOL' => 100,
                    'KOD' => '10',
                    'ODD' => '2017-01-08 00:00:00',
                    'DOD' => '2017-01-09 00:00:00',
                    'OPIS' => '',
                    'RODZAJ' => 'Urlop taryfowy',
                    'GRUPA' => 'UT',
                    'LABEL' => 'W' 
                ],
                [
                    'NAZWISKO' => 'ANTONOWICZ',
                    'IMIE' => 'MONIKA',
                    'SYMBOL' => 100,
                    'KOD' => '10',
                    'ODD' => '2017-01-10 00:00:00',
                    'DOD' => '2017-01-11 00:00:00',
                    'OPIS' => '',
                    'RODZAJ' => 'Urlop taryfowy',
                    'GRUPA' => 'UT',
                    'LABEL' => 'W' 
                ],
            ]
        ];
        //laczy urlopy jesli sie stykaja i liczy sume dni na koncu
        $this->zlaczOkresy($daneUrlopy);
        
        $this->dump($daneUrlopy); 
        die();    
    }
    
    protected function zlaczOkresy($daneUrlopy){
        $ret = [];
        foreach($daneUrlopy as $k => $dni){
            $ret[$k] = [];
            for($i = 0; $i < count($dni); $i++){
                if($i == 0){
                    $poprzedni = $dni[$i];
                }else{
                    //porownujemy obecny z poprzednim
                    $d1 = new \Datetime($poprzedni['DOD']);
                    $d2 = new \Datetime($dni[$i]['ODD']);
                    $diff = $d2->diff($d1);
                    if($diff->format("%d") == "1"){
                        //znaczy ze mamy dzien roznicy
                        $poprzedni['DOD'] = $dni[$i]['DOD'];
                    }else{
                        $ret[$k][] = $this->policzDni($poprzedni);
                        $poprzedni = $dni[$i];
                    }
                    
                    
                    //var_dump($d1, $d2, $diff, $diff->format("%d")); die();
                }
            }
            $ret[$k][] = $this->policzDni($poprzedni);
        }
        $this->dump($ret); die();
        return $ret;
    }
    
    protected function policzDni($poprzedni){  
        $d1 = new \Datetime($poprzedni['ODD']);      
        $d2 = new \Datetime($poprzedni['DOD']);
        $diff = $d2->diff($d1);
        $poprzedni['dni'] = $diff->format("%d")+1;
        return $poprzedni;
    }
    
    protected function grupujUrlopyOsob($dane){
        $ret = [];
        foreach($dane as $d){
            if(!isset($ret[$d['SYMBOL']])){
                $ret[$d['SYMBOL']] = [];
            }
            $ret[$d['SYMBOL']][] = $d;
        }
        return $ret;
    }
    protected function dodajDniWolneIDodatkoweyUrlopy(&$daneUrlopy, $dodatkoweDni){
        //$this->dump($dodatkoweDni); die();
        foreach($dodatkoweDni as $k => $dni){
            if($k == 0){
                //normalne dni wolne , dodac do wszystkich
                foreach($daneUrlopy as &$du){
                    foreach($dni as $d){
                        $du[] = $this->ustandaryzujDane($d);
                    }
                }
            }else{
                foreach($dni as $d){
                    $daneUrlopy[$k][] = $this->ustandaryzujDane($d);
                }
                
            }
        }
    }
    protected function sortujDane(&$daneUrlopy){
        foreach($daneUrlopy as $k => $d){
            usort($daneUrlopy[$k], 
                function($a, $b){
                    return $a['ODD'] > $b['ODD'];
                }
            );
        }       
    }
    
    protected function ustandaryzujDane($d){
        $dzien = $d['ROK']."-".\str_pad($d['MIESIAC'], 2, '0', \STR_PAD_LEFT)."-".\str_pad($d['DZIEN'], 2, '0', \STR_PAD_LEFT);
        return [
            'NAZWISKO' => $d['NAZWISKO'],
            'IMIE' => $d['IMIE'],
            'SYMBOL' => $d['SYMBOL'],
            'KOD' => "",
            'ODD' => $dzien,
            'DOD' => $dzien,
            'OPIS' => ($d['SYMBOL'] == "0" ? "Dzien wolny" : "???"),
            'RODZAJ' => ($d['SYMBOL'] == "0" ? "Dzien wolny" : "???"),
            'GRUPA' => 'DW',
            'LABEL' => 'W',    
        ];
    }
    
    protected function dump($r){
        echo "<pre>"; print_r($r); echo "</pre>";
    }
    protected function getSqlUrlopy(){
        $dataKoniec = date('m/d/Y');
        $dataPoczatek = new \Datetime();
        $dataPoczatek->sub(new \DateInterval('P'.$this->ileDni.'D'));
        $dataPoczatek = $dataPoczatek->format('m/d/Y');
        
        $sql = "
        select
        p.nazwisko,
        p.imie,
        p.symbol,
        a.kod,a.odd, a.dod, a.opis, n.opis rodzaj ,n.rodzaj grupa, n.label           
        from 
        p_absencja a, p_nieobec n, p_pracownik p
        where
        a.kod = n.kod and
        p.symbol = a.symbol and 
        ((a.odd between timestamp '$dataPoczatek'  and timestamp '$dataKoniec' ) or 
        (a.dod between timestamp '$dataPoczatek'  and timestamp '$dataKoniec' ) or 
        (timestamp '$dataPoczatek'  between a.odd and a.dod))
        order by p.symbol, a.odd
        ";
        
        
        //var_dump($dataPoczatek, $dataKoniec, $sql); die();
        return $sql;
    }
    protected function getSqlDniWolne($rok){
        $sql = "with kalendarz as                 
            (
            select 
            0 as symbol,
            miesiac, dzien, typ, 0 source 
            from 
            p_swieto_zm 
            where 
            rok = $rok
            
            union
            
            select 
            m.symbol,
            miesiac, dzien, typ, 2 source 
            from 
            p_swieto_mp s, pv_mp_pra m
            where 
            s.rok = $rok and 
            s.kod = m.kod and
            cast(s.miesiac || '-' || s.dzien || '-' || s.rok as timestamp) between m.data_od and coalesce(m.data_do, cast(s.miesiac || '-' || s.dzien || '-' || s.rok as timestamp))
            
            union
            
            select 
            symbol,
            miesiac, dzien, typ, 3 source 
            from 
            p_swieto_pra
            where
            rok = $rok 
            
            union 
            
            select 
            symbol,
            miesiac, dzien, typ, 4 source 
            from 
            p_pra_harm 
            where 
            rok = $rok
            
            union 
            
            select 
            r.symbol,
            extract(MONTH from o.data) miesiac, extract(DAY from o.data) dzien,  1 typ, 5 source
            from
            p_rcpwybg r 
            left JOIN ps_okres(r.odd, r.dod) o ON 1=1
            where 
            extract(YEAR from o.data) = $rok
            )
            
            
            select 
p.nazwisko, p.imie, $rok as rok, k.* from kalendarz k 
left join p_pracownik p on p.symbol = k.symbol
where k. source  = (select max(source) from kalendarz k2 where k.miesiac = k2.miesiac and k.dzien = k2.dzien) order by k.symbol ";

        return $sql;

    }
    
}