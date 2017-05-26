<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Services\ParpMailerService;
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

/**
 * Klaster controller.
 *
 * @Route("/devtest")
 */
class DevTestController extends Controller
{
    public function zrobOuAction(){

        die('done');
    }

    /**
     * @Route("/zmianyNaPodstawieWypchniecDoAD/{zasob}", name="zmianyNaPodstawieWypchniecDoAD")
     * @Template()
     */
    public function zmianyNaPodstawieWypchniecDoADAction($zasob){
        $wyniki = $this->getNadanieOdebranieUprawnien($zasob, true);


        $wyniki = array_merge($wyniki, $this->getNadanieOdebranieUprawnien($zasob, false));

        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wyniki]);
    }
    protected function getNadanieOdebranieUprawnien($zasob, $nadanie){
        $wyniki = [];
        $em = $this->getDoctrine()->getManager();
        $historia = $em->getRepository('ParpMainBundle:Entry')->createQueryBuilder('o')
            ->where('o.memberOf LIKE :groupa')
            ->andWhere('o.fromWhen >= \'2017-01-01 00:00:00\'')
            ->setParameter('groupa', '%'.($nadanie ? '+' : '-').$zasob.'%')
            ->getQuery()
            ->getResult();
        //var_dump($historia);

        foreach($historia as $h){
            $wyniki[] = [
                'data' => $h->getFromWhen(),
                'zasob' => $zasob,
                'osoba' => $h->getSamaccountname(),
                'operacja' => ($nadanie ? 'nadanie uprawnień' : 'odebranie uprawnień'),
            ];
        }
        return $wyniki;
    }
    /**
     * @Route("/zmianyWgrupieNaPodstawieAS/{zasob}", name="zmianyWgrupieNaPodstawieAS")
     * @Template()
     */
    public function zmianyWgrupieNaPodstawieASAction($zasob){
        $em = $this->getDoctrine()->getManager();

        $historia = $em->getRepository('ParpSoapBundle:ADGroup')->createQueryBuilder('o')
            ->where('o.name LIKE :groupa')
            ->andWhere('o.createdAt >= \'2017-01-01 00:00:00\'')
            ->setParameter('groupa', $zasob)
            ->getQuery()
            ->getResult();
        //echo (count($historia).' zestawienieDWI');

        $zmiany = [];
        for($i = 0; $i < count($historia); $i++){
            if(count($zmiany) == 0){
                $zmiany[] = [
                    'data'=>$historia[$i]->getCreatedAt(),
                    'zasob' => $historia[$i]->getName(),
                    'members' => $this->parseCNNames($historia[$i]->getMember()),
                    'odeszli' => [],
                    'doszli' => []
                ];
            }else{
                $m1 = $zmiany[count($zmiany) - 1]['members'];
                $m2 = $this->parseCNNames($historia[$i]->getMember());
                $d1 = array_diff($m1, $m2);
                $d2 = array_diff($m2, $m1);
                if(count($d1) > 0 || count($d2) > 0) {
                    $zmiany[] = [
                        'data' => $historia[$i]->getCreatedAt(),
                        'zasob' => $historia[$i]->getName(),
                        'members' => $this->parseCNNames($historia[$i]->getMember()),
                        'odeszli' => $d1,
                        'doszli' => $d2
                    ];
                }
            }

        }
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $zmiany]);
        /*
        echo "<pre>";
        print_r($zmiany);
        echo "</pre>";

        return ["<html></html>"];
        */
    }
    protected function parseCNNames($members){
        $a = explode(';', $members);
        foreach($a as &$e){
            $e = $this->parseCNName($e);
        }
        return $a;
    }
    protected function parseCNName($cn){
        $ps1 = explode(",", $cn);
        $name = str_replace('CN=', '', $ps1[0]);
        $ou = str_replace('OU=', '', $ps1[1]);
        return $name." / ".$ou;
    }

}