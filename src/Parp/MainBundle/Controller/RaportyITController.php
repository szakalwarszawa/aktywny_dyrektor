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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * RaportyIT controller.
 *
 * @Security("has_role('PARP_ADMIN')")
 * @Route("/RaportyIT")
 */
class RaportyITController extends Controller
{
    protected $ldap;
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
     * @Route("/generujRaport", name="raportIT1")
     * @Template()
     */
    public function indexAction(Request $request, $rok = 0)
    {
        if(!in_array("PARP_ADMIN", $this->getUser()->getRoles())){
            throw new SecurityTestException('Nie masz dostępu do tej części aplikacji', 999);
        }
        $lata = [];
        for($i = date("Y"); $i > 2003 ; $i--){
            $lata[$i] = $i;
        }
        $builder = $this->createFormBuilder(array('csrf_protection' => false))
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
        $builder->add('miesiac', 'choice', array(
                'required' => true,
                'label' => 'Wybierz miesiąc do raportu',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'choices' => $this->miesiace,
                'attr' => array(
                    'class' => 'form-control',
                ),
                'data' => date("n")
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

            return $this->generujRaport($ndata, $this->get('ldap_service'), $this->getDoctrine()->getManager(), $this->get('samaccountname_generator'), $this->get('templating'));
            //return $this->generateExcel($data, $rok);
        }
        
        return [
            'form' => $form->createView()    
        ];
    }

    public function generujRaport($ndata, $ldap, $em, $samaccountNameGenerator, $twig){
        $this->ldap = $ldap;
        $daneRekord = $this->getData($ndata['rok'], $ndata['miesiac'], $em);

        $daneZRekorda = [];
        foreach($daneRekord as $dr){
            $daneZRekorda[$dr['login']] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac']);
        }

        $zmianyDepartamentow = [];
        $repo = $em->getRepository('ParpMainBundle:DaneRekord');
        $historyRepo = $em->getRepository('Parp\MainBundle\Entity\HistoriaWersji');
        $zmianyDep = $repo->findChangesInMonthByPole($ndata['rok'], $ndata['miesiac']);
        foreach($zmianyDep as $zmiana){
            $id = $zmiana[0]['id'];
            $wersja = $zmiana['version'];
            if($wersja > 1){

                $wpis = $repo->find($id);
                //var_dump($wpis);
                $historyRepo->revert($wpis, $wersja);
                $wpisNowy = clone $wpis;
                //var_dump($wpis);
                $historyRepo->revert($wpis, $wersja-1);
                //var_dump($wpis);
                //die();
                if($wpisNowy->getDepartament() != $wpis->getDepartament()){
                    //die("zmiana dep!!!!");
                    $dep1 = $em->getRepository('Parp\MainBundle\Entity\Departament')->findOneByNameInRekord($wpis->getDepartament());
                    $dep2 = $em->getRepository('Parp\MainBundle\Entity\Departament')->findOneByNameInRekord($wpisNowy->getDepartament());
                    $akcja = "Zmiana departamentu z '".$dep1->getName()."' na '".$dep2->getName()."'";
                    //var_dump($zmiana);
                    $dr = [
                        'login' => $wpisNowy->getLogin(),
                        'nazwisko' => $wpisNowy->getNazwisko(),
                        'imie' => $wpisNowy->getImie(),
                        'umowa' => $wpisNowy->getUmowa(),
                        'umowaOd' => $wpisNowy->getUmowaOd(),
                        'umowaDo' => $wpisNowy->getUmowaDo(),
                        'dataZmiany' => $zmiana['loggedAt']->format("Y-m-d"),
                    ];

                    $daneZRekorda[$wpis->getLogin()] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac'], $akcja);
                }
            }
        }


        $users = $ldap->getAllFromADIntW('wszyscyWszyscy');
        //var_dump($users); die();
        //$daneAD = [];
        $miesiac = str_pad($ndata['miesiac'], 2, '0', STR_PAD_LEFT);
        foreach($users as $u){
            if($u['accountExpires'] /* && $u['samaccountname'] == "leszek_czech" */ ){
                $rok = explode("-", $u['accountexpires'])[0];
                $dataExpire = \DateTime::createFromFormat('Y-m-d', $u['accountExpires']);

                if($u['samaccountname'] == 'leszek_czech'){
                    //var_dump($rok,  date("Y"), $u, $dataExpire); die('b');
                }

                if($rok == date("Y")){
                    if($rok < 3000 && $dataExpire->format("Y-m") == $ndata['rok']."-".$miesiac){
                        //$akcja = 'Nowa osoba przyszła do pracy';
                        //$dataZmiany = $dr['umowaOd']->format("Y-m-d");
                        if(!isset($daneZRekorda[$u['samaccountname']])){
                            $danaRekord = $repo->findOneByLogin($u['samaccountname']);
                            if($danaRekord){
                                $dr = [
                                    'login' => $danaRekord->getLogin(),
                                    'nazwisko' => $danaRekord->getNazwisko(),
                                    'imie' => $danaRekord->getImie(),
                                    'umowa' => $danaRekord->getUmowa(),
                                    'umowaOd' => $danaRekord->getUmowaOd(),
                                    'umowaDo' => $danaRekord->getUmowaDo(),
                                    'dataZmiany' => $u['accountexpires'],
                                ];
                            }else{
                                $rozbite = $samaccountNameGenerator->rozbijFullname($u['name']);
                                $dr = [
                                    'login' => $u['samaccountname'],
                                    'nazwisko' => $rozbite['nazwisko'],
                                    'imie' => $rozbite['imie'],
                                    'umowa' => "__Brak danych w REKORD",
                                    'umowaOd' => "__Brak danych w REKORD",
                                    'umowaDo' => "__Brak danych w REKORD",
                                    'dataZmiany' => $u['accountexpires'],
                                ];
                            }
                            $daneZRekorda[$u['samaccountname']] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac'], 'wygaszenie konta w AD');
                        }
                    }
                }
            }
        }
        //die(); //przeniesc na koniec !!!!!!



        return $twig->render('ParpMainBundle:RaportyIT:wynik.html.twig', ['daneZRekorda' => $daneZRekorda, 'rok' => $ndata['rok'], 'miesiac' => $miesiac ]);
    }

    protected function parseManagerDN($dn){
        if(strstr($dn, "=") !== false) {
            //CN=Pokorski Jacek,OU=DAS,OU=Zespoly_2016,OU=PARP Pracownicy,DC=parp,DC=local
            //echo $dn . ".";
            $p = explode("=", $dn);
            $p2 = explode(",", $p[1]);
            return $p2[0];
        }else{
            return "";
        }
    }
    protected function zrobRekordZRekorda($dr, $rok, $miesiac, $akcja = ''){
        $miesiac = str_pad($miesiac, 2, '0', STR_PAD_LEFT);
        //die($rok."-".$miesiac);
        $dataZmiany = "";
        $ldap = $this->ldap;
        $user = $ldap->getUserFromAD($dr['login'], null, null, 'wszyscyWszyscy' );
        //var_dump($user, $dr); //die();
        if($akcja == ''){
            $akcja = '';
            //echo ($dr['umowaOd']->format("Y-m") ."___". $rok."-".$miesiac);
            if($dr['umowaOd']->format("Y-m") == $rok."-".$miesiac){
                $akcja = 'Nowa osoba przyszła do pracy';
                $dataZmiany = $dr['umowaOd']->format("Y-m-d");
            }
            else if($dr['umowaDo'] && $dr['umowaDo']->format("Y-m") == $rok."-".$miesiac){
                $akcja = 'Osoba odeszła z pracy';
                $dataZmiany = $dr['umowaDo']->format("Y-m-d");
            }
        }
        return [
            'login' => $dr['login'],
            'nazwisko' => $dr['nazwisko'],
            'imie' => $dr['imie'],
            'departament' => $user[0]['department'],
            'sekcja' => $user[0]['info'],
            'stanowisko' => $user[0]['title'],
            'przelozony' => $this->parseManagerDN($user[0]['manager']),
            'umowa' => $dr['umowa'],
            'umowaOd' => $dr['umowaOd'],
            'umowaDo' => $dr['umowaDo'],
            'expiry' => $user[0]['accountexpires'],
            'akcja' => $akcja,
            'data' => (isset($dr['dataZmiany']) ? $dr['dataZmiany'] : $dataZmiany),
        ];
    }
    protected function getData($rok, $miesiac, $em){
        return $em->getRepository('ParpMainBundle:DaneRekord')->findChangesInMonth($rok, $miesiac);
    }
    /**
     *
     * @Route("/tempTest", name="tempTest")
     * @Template()
     */
    public function tempTestAction(Request $request, $rok = 0)
    {
        die('dzialam');

    }
    protected function makeRowVersion($eNext, $log, &$datyKonca){
        $ret = [];
        $grupy = ['departament', 'stanowisko'];
        foreach($log->getData() as $l => $v) {
            if(in_array($l, $grupy)) {
                $content = $v;
                if($l == 'departament'){
                    $content = isset($this->departamenty[$v]) ? $this->departamenty[$v]->getName() : 'stary departament';
                }
                $group = $l;
                $dana = [
                    'id' => \uniqid().$l.''.$log->getId(),
                    'content' => $content,
                    'title' => $content,
                    'start' => $log->getLoggedAt(),
                    'end' => $datyKonca[$l],
                    'group' => $group
                ];
                if($this->zakres['min'] == null || $this->zakres['min'] > $log->getLoggedAt()){
                    $this->zakres['min'] = $log->getLoggedAt();
                }
                if($this->zakres['max'] == null || $this->zakres['max'] < $datyKonca[$l]){
                    $this->zakres['max'] = $datyKonca[$l];
                }
                $ret[] = $dana;
                $datyKonca[$l] = $log->getLoggedAt();
                if($l == 'departament'){
                    $dana['id'] = $dana['id'].md5($content);
                    $dana['type'] = 'background';
                    $dana['className'] = 'tloWykresu'.$this->className++;
                    unset($dana['group']);
                    if($this->ostatniDepartament == null) {
                        $this->ostatniDepartament = $dana;
                        $this->ostatniDepartament['daneRekord'] = $eNext;
                        $this->ostatniDepartament['departament'] = $this->departamenty[$v]->getShortname();
                        $this->ostatniDepartament['sekcja'] = $this->user['division'];
                        $this->user['title'] = $eNext->getStanowisko();
                    }
                    $ret[] = $dana;
                }
            }
        }
        return $ret;
    }
    protected $departamenty = [];
    protected $grupy = [];
    protected $zakres = ['min' => null, 'max' => null];
    protected $className = 1;
    protected $ostatniDepartament = null;
    protected $sumaUprawnien = [];
    protected $user = null;
    /**
     * @Route("/raportBss/{login}", name="raportBss")
     * @Template()
     */
    public function raportBssAction($login = 'kamil_jakacki'){
        $now = new \Datetime();
        $datyKonca = [
            'departament' => $now,
            'sekcja' => $now,
            'stanowisko' => $now,
        ];
        $em = $this->getDoctrine()->getManager();

        $this->user = $this->get('ldap_service')->getUserFromAD($login, null, null, 'wszyscyWszyscy' )[0];

        $this->departamenty = $em->getRepository('ParpMainBundle:Departament')->bierzDepartamentyNowe();
        //pobierz dane zmiany departamentow, stanowisk
        $entity = $em->getRepository('ParpMainBundle:DaneRekord')->findOneByLogin($login);

        $repo = $em->getRepository('Parp\MainBundle\Entity\HistoriaWersji'); // we use default log entry class
        $logs = $repo->getLogEntries($entity);
        $dane = [];
        foreach($logs as $log){
            $entityTemp = clone $entity;
            $repo->revert($entityTemp, $log->getVersion());
            $dane = array_merge($dane, $this->makeRowVersion($entity, $log, $datyKonca));
        }

        foreach($dane as $d){
            $uzs = $em->getRepository('ParpMainBundle:UserZasoby')->findDlaOsoby($login, $d['start'], $d['end']);
            foreach($uzs as $uz){
                $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($uz->getZasobId());
                $do = $uz->getAktywneDo() ? ($uz->getAktywneDo() < $d['end'] ? $uz->getAktywneDo() : $d['end']) : $d['end'];
                $c = ' <a href="'.$this->generateUrl('zasoby_edit', ['id' => $zasob->getId()]).'">'.$zasob->getNazwa().'</a>';
                $dana = [
                    'id' => \uniqid().'Zasob'.$zasob->getId(),
                    'content' => $c,
                    'title' => $zasob->getNazwa(),
                    'start' => $uz->getAktywneOd(),
                    'end' => $do,
                    'group' => 'zasoby',
                    'userzasoby' => $uz,
                    'zasob' => $zasob
                ];
                $dane[] = $dana;
            }
        }
        $dane = $this->przygotujDaneRaportuBss($dane);
        //var_dump($dane);
        //die();

        return $this->render('ParpMainBundle:Dev:wykresBss.html.twig', [
            'login' => $login,
            'dane' => json_encode($dane),
            'zakresMin' => $this->zakres['min']->format('Y-m-d'),
            'zakresMax' => $this->zakres['max']->format('Y-m-d'),
        ]);
    }
    protected function przygotujDaneRaportuBss(&$dane){
        $now = new \Datetime();
        usort($dane, function($a, $b){
            return $a['start'] < $b['start'];
        });
        $okresNr = 1;
        $okresy = [];
        for($i = 0; $i < count($dane); $i++){
            if(
                $dane[$i]['start'] >= $this->ostatniDepartament['start'] &&
                $dane[$i]['start'] <= $this->ostatniDepartament['end']
            ){
                if($this->sumaUprawnien == null){
                    $this->sumaUprawnien = [
                        'id' => 'sumaUprawnien12',
                        'start' => $this->ostatniDepartament['start'],
                        'end' => $this->ostatniDepartament['end'],
                        'content' => 'Suma uprawnień które powinny być obecnie',
                        'title' => '',
                        'group' => 'suma',
                        'grupy' => $this->get('ldap_service')->getGrupyUsera($this->user, $this->ostatniDepartament['departament'], $this->ostatniDepartament['sekcja'])
                    ];
                }
                if(isset($dane[$i]['group']) && $dane[$i]['group'] == 'zasoby') {
                    $grupy = $dane[$i]['zasob']->getGrupyADdlaPoziomu($dane[$i]['userzasoby']->getPoziomDostepu());
                    //var_dump($grupy);
                    if($grupy){
                        //var_dump($this->user);
                        $this->sumaUprawnien['grupy'] = isset($this->sumaUprawnien['grupy']) ? $this->sumaUprawnien['grupy'] : [];
                        $this->sumaUprawnien['grupy'] = array_merge($grupy, $this->sumaUprawnien['grupy']);
                    }
                    //die();

                    unset($dane[$i]['userzasoby']);
                    unset($dane[$i]['zasob']);
                    if ($this->sumaUprawnien['start'] < $dane[$i]['start']) {
                        $this->sumaUprawnien['start'] = $dane[$i]['start'];
                    }
                }

            }
            $dane[$i]['start'] = $dane[$i]['start']->format('Y-m-d');
            $dane[$i]['end'] = $dane[$i]['end'] ? $dane[$i]['end']->format('Y-m-d') : $now->format('Y-m-d');
        }
        $this->sumaUprawnien['grupy'] = isset($this->sumaUprawnien['grupy']) ? $this->sumaUprawnien['grupy'] : [];
        $this->sumaUprawnien['grupy'] = array_unique(array_filter($this->sumaUprawnien['grupy']));
        $this->sumaUprawnien['title'] = implode(", <br>", $this->sumaUprawnien['grupy']);

        $content = $this->sumaUprawnien['content'].': <br><br>';
        $this->sumaUprawnien['brakujace'] = [];
        foreach($this->sumaUprawnien['grupy'] as $g){
            if(!in_array($g, $this->user['memberOf'])){
                $content .= '<span style="color:red"><b>'.$g.'</b></span><br>';
                $this->sumaUprawnien['brakujace'][] = $g;
            }else {
                $content .= $g.'<br>';
            }
        }
        $content .= '<br><a href="'.$this->generateUrl('nadajGrupy', ['login' => $this->user['samaccountname'], 'grupy' => implode(',', $this->sumaUprawnien['grupy'])]).'" class="btn btn-success" target="_blank">NAPRAW</a>';


        $this->sumaUprawnien['content'] = $content;
        $this->sumaUprawnien['start'] = is_string($this->sumaUprawnien['start']) ? $this->sumaUprawnien['start'] : $this->sumaUprawnien['start']->format('Y-m-d');
        $this->sumaUprawnien['end'] = is_string($this->sumaUprawnien['end']) ? $this->sumaUprawnien['end'] :$this->sumaUprawnien['end']->format('Y-m-d');
        unset($this->sumaUprawnien['grupy']);

        //var_dump($this->sumaUprawnien);

        $dane[] = $this->sumaUprawnien;

        usort($dane, function($a, $b){
            return $a['start'] > $b['start'];
        });

        return $dane;
    }

    /**
     * @Route("/nadajGrupy/{login}/{grupy}", name="nadajGrupy")
     * @Template()
     */
    public function nadajGrupyAction($login = 'kamil_jakacki', $grupy = ''){
        $grupy = explode(',', $grupy);
        $entry = new \Parp\MainBundle\Entity\Entry();
        $entry->setFromWhen(new \Datetime());
        $entry->setSamaccountname($login);
        $entry->setMemberOf('+'.implode(',+', $grupy));
        $entry->setCreatedBy($this->getUser()->getUsername());
        $entry->setOpis("Przywracanie uprawnien za pomoca linku.");
        $this->getDoctrine()->getManager()->persist($entry);
        //$this->getDoctrine()->getManager()->flush();
        var_dump($login, $grupy, $entry);
        //die();
    }



    /**
     * @Route("/poprawKierownictwo", name="poprawKierownictwo")
     * @Template()
     */
    public function poprawKierownictwoAction(){
        $dyrs = $this->get('ldap_service')->getZarzad();
        $braki = [];
        $pomin = ['fsdds_fdsf', 'testowy_test'];
        foreach($dyrs as $d){
            if(!in_array($d['samaccountname'], $pomin)) {
                if(strpos($d['useraccountcontrol'], 'ACCOUNTDISABLE') === false){
                    $this->raportBssAction($d['samaccountname']);
                    $braki[$d['samaccountname']] = $this->sumaUprawnien['brakujace'];
                    $this->nadajGrupyAction($d['samaccountname'], implode(',', $this->sumaUprawnien['brakujace']));
                    //var_dump($braki); die();
                    $this->departamenty = [];
                    $this->grupy = [];
                    $this->zakres = ['min' => null, 'max' => null];
                    $this->className = 1;
                    $this->ostatniDepartament = null;
                    $this->sumaUprawnien = [];$this->user = null;
                }
            }
        }
        var_dump($braki);
        //$this->getDoctrine()->getManager()->flush();
    }


}