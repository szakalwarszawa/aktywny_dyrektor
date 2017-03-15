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
    /**
     * @Route("/ponad21", name="ponad21")
     * @Template()
     */
    public function testAdLib3Action()
    {
        
        $sql = $this->getSqlUrlopy();

        $c = new ImportRekordDaneController();
        $c->setContainer($this->container);
        $dane = $c->executeQuery($this->getSqlUrlopy()); 
        var_dump($dane); 
        die();    
    }
    
    protected function getSqlUrlopy(){
        $dataKoniec = date('m/d/Y');
        $dataPoczatek = new \Datetime();
        $dataPoczatek->sub(new \DateInterval('P30D'));
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
        ";
        
        
        //var_dump($dataPoczatek, $dataKoniec, $sql); die();
        return $sql;
    }
    
}