<?php

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\Entry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use ParpV1\MainBundle\Exception\SecurityTestException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\WniosekHistoriaStatusow;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\HistoriaWersji;
use DateTime;
use ParpV1\MainBundle\Entity\DaneRekord;
use Exception;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\MainBundle\Entity\WniosekStatus;
use ParpV1\MainBundle\Services\ParpMailerService;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Constants\WyzwalaczeConstants;
use ParpV1\MainBundle\Entity\OdebranieZasobowEntry;

/**
 * RaportyIT controller.
 * @Security("has_role('PARP_ADMIN')")
 * @Route("/RaportyIT")
 *
 * @todo to jest do usunięcia całkowitego
 */
class RaportyITController extends Controller
{
    public $container = null;
    public $brakujaceGrupy = [];
    protected $ldap;
    protected $miesiace = [
        '1'  => 'Styczeń',
        '2'  => 'Luty',
        '3'  => 'Marzec',
        '4'  => 'Kwiecień',
        '5'  => 'Maj',
        '6'  => 'Czerwiec',
        '7'  => 'Lipiec',
        '8'  => 'Sierpień',
        '9'  => 'Wrzesień',
        '10' => 'Październik',
        '11' => 'Listopad',
        '12' => 'Grudzień',
    ];
    protected $departamenty = [];
    protected $grupy = [];
    protected $zakres = ['min' => null, 'max' => null];
    protected $className = 1;
    protected $ostatniDepartament = null;
    protected $sumaUprawnien = [];
    protected $user = null;

    /**
     * @var array
     */
    private $logWpis = [];

    /**
     * @Route("/generujRaport", name="raportIT1")
     * @Template()
     * @param Request $request
     *
     * @return array|string
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws SecurityTestException
     */
    public function indexAction(Request $request)
    {
        if (!in_array('PARP_ADMIN', $this->getUser()->getRoles(), true)) {
            throw new AccessDeniedException('Nie masz dostępu do tej części aplikacji');
        }

        $lata = [];
        for ($i = date('Y'); $i > 2003; $i--) {
            $lata[$i] = $i;
        }

        $builder = $this->createFormBuilder()
            ->add('rok', ChoiceType::class, array(
                'required'   => true,
                'label'      => 'Wybierz rok do raportu',
                'label_attr' => array(
                    'class' => 'wybierz-rok',
                ),
                'choices'    => $lata,
                'attr'       => array(
                    'class' => 'form-control',
                ),
            ))
            ->add('miesiac', ChoiceType::class, array(
            'required'   => true,
            'label'      => 'Wybierz miesiąc do raportu',
            'label_attr' => array(
                'class' => 'wybierz-miesiac',
            ),
            'choices'    => $this->miesiace,
            'attr'       => array(
                'class' => 'form-control',
            ),
            'data'       => date('n'),
            ))
            ->add('zapisz', SubmitType::class, array(
            'attr' => array(
                'class' => 'btn btn-success col-sm-12',
            ),
            ));

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();

            $raport = $this->generujRaport(
                $ndata,
                $this->get('ldap_service'),
                $this->getDoctrine()->getManager(),
                $this->get('samaccountname_generator')
            );

            return $this->render(
                'ParpMainBundle:RaportyIT:wynik.html.twig',
                [
                    'daneZRekorda' => $raport,
                    'rok' => $ndata['rok'],
                    'miesiac' => $ndata['miesiac']
                ]
            );
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * Akcja, która na podstawie miesiąca oraz roku generuje raport do pliku XLS
     *
     * @Route("/generujRaport/XLS/{miesiac}/{rok}", name="raport_it_generuj_xls")
     * @param int $miesiac
     * @param int $rok
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function generujExcelAction($miesiac = null, $rok = null)
    {
        $data = new DateTime();
        $rok = null === $rok ? $data->format('Y') : $rok;
        $miesiac = null === $miesiac ? $data->format('m') : $miesiac;

        if ($miesiac < 1 || $miesiac > 12) {
            throw new InvalidArgumentException('Miesiąc musi być z zakresu 1 <> 12');
        }

        if ($rok < 2006 || $rok > 2025) {
            throw new InvalidArgumentException('Rok musi być z zakresu 2006 <> 2025');
        }

        $raport = $this->generujRaport(
            ['rok' => $rok, 'miesiac' => $miesiac],
            $this->get('ldap_service'),
            $this->getDoctrine()->getManager(),
            $this->get('samaccountname_generator')
        );

        // Trzeba spłaszczyć tablicę, aby była "zjadalna" przez Excela
        $excelData = [];
        $i = 0;
        foreach ($raport as $item) {
            $excelData[$i]['login'] = $item['login'];
            $excelData[$i]['nazwisko'] = $item['nazwisko'];
            $excelData[$i]['imie'] = $item['imie'];
            $excelData[$i]['departament'] = $item['departament'];
            $excelData[$i]['sekcja'] = $item['sekcja'];
            $excelData[$i]['stanowisko'] = $item['stanowisko'];
            $excelData[$i]['przelozony'] = $item['przelozony'];
            $excelData[$i]['umowa'] = $item['umowa'];
            $excelData[$i]['umowaOd'] = ($item['umowaOd']);
            $excelData[$i]['umowaDo'] = ($item['umowaDo']);
            $excelData[$i]['expiry'] = $item['expiry'];
            $excelData[$i]['akcja'] = $item['akcja'];
            $excelData[$i]['data'] = $item['data'];
            $i++;
        }

        return $this->get('excel_service')->generateExcel($excelData);
    }

    /**
     * @param $ndata
     * @param $ldap
     * @param ObjectManager $em
     * @param $samaccountNameGenerator
     * @return mixed
     * @internal param $twig
     */
    public function generujRaport($ndata, $ldap, ObjectManager $em, $samaccountNameGenerator)
    {
        $this->ldap = $ldap;
        $daneRekord = $this->getData($ndata['rok'], $ndata['miesiac'], $em);

        $daneZRekorda = [];
        foreach ($daneRekord as $dr) {
            $daneZRekorda[$dr['login']] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac']);
        }

        $repo = $em->getRepository(DaneRekord::class);
        $historyRepo = $em->getRepository(HistoriaWersji::class);
        $zmianyDep = $repo->findChangesInMonthByPole($ndata['rok'], $ndata['miesiac']);
        foreach ($zmianyDep as $zmiana) {
            $id = $zmiana[0]['id'];
            $wersja = $zmiana['version'];
            if ($wersja > 1) {
                $wpis = $repo->find($id);
                //var_dump($wpis);
                $historyRepo->revert($wpis, $wersja);
                $wpisNowy = clone $wpis;
                //var_dump($wpis);
                $historyRepo->revert($wpis, $wersja - 1);
                //var_dump($wpis);
                //die();
                if ($wpisNowy->getDepartament() != $wpis->getDepartament()) {
                    //die("zmiana dep!!!!");
                    $dep1 =
                        $em->getRepository(Departament::class)
                            ->findOneByNameInRekord($wpis->getDepartament());
                    $dep2 =
                        $em->getRepository(Departament::class)
                            ->findOneByNameInRekord($wpisNowy->getDepartament());
                    $akcja = "Zmiana departamentu z '" . $dep1->getName() . "' na '" . $dep2->getName() . "'";
                    //var_dump($zmiana);
                    $dr = [
                        'login'      => $wpisNowy->getLogin(),
                        'nazwisko'   => $wpisNowy->getNazwisko(),
                        'imie'       => $wpisNowy->getImie(),
                        'umowa'      => $wpisNowy->getUmowa(),
                        'umowaOd'    => $wpisNowy->getUmowaOd(),
                        'umowaDo'    => $wpisNowy->getUmowaDo(),
                        'dataZmiany' => $zmiana['loggedAt']->format('Y-m-d'),
                    ];

                    $daneZRekorda[$wpis->getLogin()] =
                        $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac'], $akcja);
                }
            }
        }

        $users = $ldap->getAllFromADIntW('wszyscyWszyscy');
        //var_dump($users); die();
        //$daneAD = [];
        $miesiac = str_pad($ndata['miesiac'], 2, '0', STR_PAD_LEFT);
        foreach ($users as $u) {
            if ($u['accountExpires']) {
                $rok = explode('-', $u['accountexpires'])[0];
                $dataExpire = DateTime::createFromFormat('Y-m-d', $u['accountExpires']);

                if ($rok == date('Y')) {
                    if ($rok < 3000 && $dataExpire->format('Y-m') == $ndata['rok'] . '-' . $miesiac) {
                        //$akcja = 'Nowa osoba przyszła do pracy';
                        //$dataZmiany = $dr['umowaOd']->format("Y-m-d");
                        if (!isset($daneZRekorda[$u['samaccountname']])) {
                            $danaRekord = $repo->findOneByLogin($u['samaccountname']);
                            if ($danaRekord) {
                                $dr = [
                                    'login'      => $danaRekord->getLogin(),
                                    'nazwisko'   => $danaRekord->getNazwisko(),
                                    'imie'       => $danaRekord->getImie(),
                                    'umowa'      => $danaRekord->getUmowa(),
                                    'umowaOd'    => $danaRekord->getUmowaOd(),
                                    'umowaDo'    => $danaRekord->getUmowaDo(),
                                    'dataZmiany' => $u['accountexpires'],
                                ];
                            } else {
                                $rozbite = $samaccountNameGenerator->rozbijFullname($u['name']);
                                $dr = [
                                    'login'      => $u['samaccountname'],
                                    'nazwisko'   => $rozbite['nazwisko'],
                                    'imie'       => $rozbite['imie'],
                                    'umowa'      => '__Brak danych w REKORD',
                                    'umowaOd'    => '__Brak danych w REKORD',
                                    'umowaDo'    => '__Brak danych w REKORD',
                                    'dataZmiany' => $u['accountexpires'],
                                ];
                            }
                            $daneZRekorda[$u['samaccountname']] =
                                $this->zrobRekordZRekorda(
                                    $dr,
                                    $ndata['rok'],
                                    $ndata['miesiac'],
                                    'wygaszenie konta w AD'
                                );
                        }
                    }
                }
            }
        }

        return $daneZRekorda;
    }

    /**
     * @param $dn
     * @return string
     */
    protected function parseManagerDN($dn)
    {
        if (strstr($dn, '=') !== false) {
            $p = explode('=', $dn);
            $p2 = explode(',', $p[1]);

            return $p2[0];
        } else {
            return '';
        }
    }

    /**
     * @param $dr
     * @param $rok
     * @param $miesiac
     * @param string $akcja
     * @return array
     */
    protected function zrobRekordZRekorda($dr, $rok, $miesiac, $akcja = '')
    {
        $miesiac = str_pad($miesiac, 2, '0', STR_PAD_LEFT);
        //die($rok."-".$miesiac);
        $dataZmiany = '';
        $ldap = $this->ldap;
        $user = $ldap->getUserFromAD($dr['login'], null, null, 'wszyscyWszyscy');
        //var_dump($user, $dr); //die();
        if ($akcja == '') {
            $akcja = '';
            //echo ($dr['umowaOd']->format("Y-m") ."___". $rok."-".$miesiac);
            if ($dr['umowaOd']->format('Y-m') == $rok . '-' . $miesiac) {
                $akcja = 'Nowa osoba przyszła do pracy';
                $dataZmiany = $dr['umowaOd']->format('Y-m-d');
            } else {
                if ($dr['umowaDo'] && $dr['umowaDo']->format('Y-m') == $rok . '-' . $miesiac) {
                    $akcja = 'Osoba odeszła z pracy';
                    $dataZmiany = $dr['umowaDo']->format('Y-m-d');
                }
            }
        }

        return [
            'login'       => $dr['login'],
            'nazwisko'    => $dr['nazwisko'],
            'imie'        => $dr['imie'],
            'departament' => $user[0]['department'],
            'sekcja'      => $user[0]['info'],
            'stanowisko'  => $user[0]['title'],
            'przelozony'  => $this->parseManagerDN($user[0]['manager']),
            'umowa'       => $dr['umowa'],
            'umowaOd'     => $dr['umowaOd'],
            'umowaDo'     => $dr['umowaDo'],
            'expiry'      => $user[0]['accountexpires'],
            'akcja'       => $akcja,
            'data'        => (isset($dr['dataZmiany']) ? $dr['dataZmiany'] : $dataZmiany),
        ];
    }

    /**
     * @param $rok
     * @param $miesiac
     * @param $em
     * @return mixed
     */
    protected function getData($rok, $miesiac, $em)
    {
        return $em->getRepository(DaneRekord::class)->findChangesInMonth($rok, $miesiac);
    }

    /**
     * @param $eNext
     * @param $log
     * @param $datyKonca
     * @return array
     */
    protected function makeRowVersion($eNext, $log, &$datyKonca)
    {
        $ret = [];
        $grupy = ['departament', 'stanowisko'];
        foreach ($log->getData() as $l => $v) {
            if (in_array($l, $grupy, true)) {
                $content = $v;
                if ($l == 'departament') {
                    $content =
                        isset($this->departamenty[$v]) ? $this->departamenty[$v]->getName() : 'stary departament';
                }
                $group = $l;
                $dana = [
                    'id'        => \uniqid() . $l . '' . $log->getId(),
                    'content'   => $content,
                    'kolejnosc' => 1,
                    'title'     => $content,
                    'start'     => $log->getLoggedAt(),
                    'end'       => $datyKonca[$l],
                    'group'     => $group,
                ];
                if ($this->zakres['min'] == null || $this->zakres['min'] > $log->getLoggedAt()) {
                    $this->zakres['min'] = $log->getLoggedAt();
                }
                if ($this->zakres['max'] == null || $this->zakres['max'] < $datyKonca[$l]) {
                    $this->zakres['max'] = $datyKonca[$l];
                }
                $ret[] = $dana;
                $datyKonca[$l] = $log->getLoggedAt();
                if ($l == 'departament') {
                    $dana['id'] = $dana['id'] . md5($content);
                    $dana['type'] = 'background';
                    $dana['className'] = 'tloWykresu' . $this->className++;
                    unset($dana['group']);
                    if ($this->ostatniDepartament == null) {
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

    /**
     * @Route("/raportBss/{login}", name="raportBss")
     * @param string $login
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function raportBssAction($login = 'kamil_jakacki')
    {
        //
        $this->container = $this;
        $dane = $this->raportBssProcesuj($login);

        return $this->render('ParpMainBundle:Dev:wykresBss.html.twig', $dane);
    }

    /**
     * @param $login
     * @return array
     */
    public function raportBssProcesuj($login)
    {
        $now = new DateTime();
        $datyKonca = [
            'departament' => $now,
            'sekcja'      => $now,
            'stanowisko'  => $now,
        ];
        $em = $this->container->getDoctrine()->getManager();

        $this->user = $this->container->get('ldap_service')->getUserFromAD($login, null, null, 'wszyscyWszyscy')[0];


        $this->departamenty = $em->getRepository(Departament::class)->bierzDepartamentyNowe();
        //pobierz dane zmiany departamentow, stanowisk
        $entity = $em->getRepository(DaneRekord::class)->findOneByLogin($login);

        if ($entity === null) {
            $min = new DateTime('2001-01-01 00:00:00');
            $max = new DateTime('2101-01-01 00:00:00');
            $dana = [
                'id'        => '54673547623',
                'content'   => 'uprawnienia',
                'kolejnosc' => 1,
                'title'     => 'stanowisko',
                'start'     => $min,
                'end'       => $max,
                'group'     => 'stanowisko',
            ];
            $dane = [
                $dana,
            ];

            $this->ostatniDepartament = $dana;
            $this->ostatniDepartament['daneRekord'] = [];
            $this->ostatniDepartament['departament'] = $this->user['description'];
            $this->ostatniDepartament['sekcja'] = $this->user['division'];
            $this->zakres['min'] = $min;
            $this->zakres['max'] = $max;
            //die('tutaj');
        } else {
            $repo = $em->getRepository(HistoriaWersji::class); // we use default log entry class
            $logs = $repo->getLogEntries($entity);
            $dane = [];
            foreach ($logs as $log) {
                $entityTemp = clone $entity;
                $repo->revert($entityTemp, $log->getVersion());
                $dane = array_merge($dane, $this->makeRowVersion($entity, $log, $datyKonca));
            }
        }
        //var_dump($dane); die();

        foreach ($dane as $d) {
            $uzs = $em->getRepository(UserZasoby::class)->findDlaOsoby($login, $d['start'], $d['end']);
            foreach ($uzs as $uz) {
                $zasob = $em->getRepository(Zasoby::class)->find($uz->getZasobId());
                $do =
                    $uz->getAktywneDo() ? ($uz->getAktywneDo() <
                    $d['end'] ? $uz->getAktywneDo() : $d['end']) : $d['end'];
                $c =
                    ' <a href="' .
                    $this->generateUrl('zasoby_edit', ['id' => $zasob->getId()]) .
                    '">' .
                    $zasob->getNazwa() .
                    '</a>';
                $dana = [
                    'id'         => \uniqid() . 'Zasob' . $zasob->getId(),
                    'content'    => $c,
                    'kolejnosc'  => 1,
                    'title'      => $zasob->getNazwa(),
                    'start'      => $uz->getAktywneOd(),
                    'end'        => $do,
                    'group'      => 'zasoby',
                    'userzasoby' => $uz,
                    'zasob'      => $zasob,
                ];
                $dane[] = $dana;
            }
        }
        $dane = $this->przygotujDaneRaportuBss($dane);
        //var_dump($dane);
        //die();
        return [
            'login'     => $login,
            'dane'      => json_encode($dane),
            'zakresMin' => $this->zakres['min']->format('Y-m-d'),
            'zakresMax' => $this->zakres['max']->format('Y-m-d'),
        ];
    }

    /**
     * @param $dane
     * @return array
     */
    protected function przygotujDaneRaportuBss(&$dane)
    {
        $now = new DateTime();
        usort($dane, function ($a, $b) {
            return $a['start'] < $b['start'];
        });
        for ($i = 0; $i < count($dane); $i++) {
            if (
                $dane[$i]['start'] >= $this->ostatniDepartament['start'] &&
                $dane[$i]['start'] <= $this->ostatniDepartament['end']
            ) {
                if ($this->sumaUprawnien == null) {
                    $this->sumaUprawnien = [
                        'id'        => 'sumaUprawnien12',
                        'start'     => $this->ostatniDepartament['start'],
                        'end'       => $this->ostatniDepartament['end'],
                        'content'   => 'Suma uprawnień które powinny być obecnie',
                        'kolejnosc' => 9,
                        'title'     => '',
                        'group'     => 'suma',
                        'grupy'     => $this->container->get('ldap_service')
                            ->getGrupyUsera(
                                $this->user,
                                $this->ostatniDepartament['departament'],
                                $this->ostatniDepartament['sekcja']
                            ),
                    ];
                }
                if (isset($dane[$i]['group']) && $dane[$i]['group'] == 'zasoby') {
                    $grupy = $dane[$i]['zasob']->getGrupyADdlaPoziomu($dane[$i]['userzasoby']->getPoziomDostepu());
                    //var_dump($grupy);
                    if ($grupy) {
                        //var_dump($this->user);
                        $this->sumaUprawnien['grupy'] =
                            isset($this->sumaUprawnien['grupy']) ? $this->sumaUprawnien['grupy'] : [];
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
        $this->sumaUprawnien['title'] = implode(', <br>', $this->sumaUprawnien['grupy']);

        $content = $this->sumaUprawnien['content'] . ': <br><br>';
        $this->sumaUprawnien['brakujace'] = [];
        foreach ($this->sumaUprawnien['grupy'] as $g) {
            if (!in_array($g, $this->user['memberOf'], true)) {
                $content .= '<span style="color:red"><b>' . $g . '</b></span><br>';
                $this->sumaUprawnien['brakujace'][] = $g;
            } else {
                $content .= $g . '<br>';
            }
        }
        $content .= '<br><a href="' .
            $this->generateUrl(
                'nadajGrupy',
                ['login' => $this->user['samaccountname'], 'grupy' => implode(',', $this->sumaUprawnien['grupy'])]
            ) .
            '" class="btn btn-success" target="_blank">NAPRAW</a>';


        $this->sumaUprawnien['kolejnosc'] = 11;
        $this->sumaUprawnien['content'] = $content;
        $this->sumaUprawnien['start'] =
            is_string($this->sumaUprawnien['start']) ? $this->sumaUprawnien['start'] : $this->sumaUprawnien['start']->format('Y-m-d');
        $this->sumaUprawnien['end'] =
            is_string($this->sumaUprawnien['end']) ? $this->sumaUprawnien['end'] : $this->sumaUprawnien['end']->format('Y-m-d');
        $this->brakujaceGrupy = $this->sumaUprawnien['grupy'];
        unset($this->sumaUprawnien['grupy']);

        //var_dump($this->sumaUprawnien);

        $dane[] = $this->sumaUprawnien;

        usort($dane, function ($a, $b) {
            return $a['kolejnosc'] > $b['kolejnosc'] && $a['start'] > $b['start'];
        });

        return $dane;
    }

    /**
     * @Route("/nadajGrupy/{login}/{grupy}", name="nadajGrupy")
     * @param string $login
     * @param string $grupy
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function nadajGrupyAction($login, $grupy)
    {
        $grupy = explode(',', $grupy);
        $entry = new Entry();
        $entry->setFromWhen(new DateTime());
        $entry->setSamaccountname($login);
        $entry->setMemberOf('+' . implode(',+', $grupy));
        $entry->setCreatedBy($this->getUser()->getUsername());
        $entry->setOpis('Przywracanie uprawnien za pomoca linku.');
        $this->getDoctrine()->getManager()->persist($entry);
        $this->getDoctrine()->getManager()->flush();

        $dane[] =  ['konto' => $login, 'grupy w AD' => $grupy];

        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $dane, 'title' => 'Nadawanie uprawnien dla: ' . $login]);
    }


    /**
     * Zwraca listę osób wraz z datą ostatniej zmiany.
     * @see https://redmine.parp.gov.pl/issues/58977
     *
     * @Route(
     *  "/przegladUprawnien/{departament}/{format}",
     *  name="przeglad_uprawnien",
     *  defaults={
     *      "departament" = "",
     *      "format" = "",
     *  }
     * )
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param string $departament
     * @param string $format
     * @param bool $returnArray
     *
     * @return JsonResponse
     */
    public function przegladZmianNaKoncieAction($departament, $returnArray = false, $format = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $zmianyUprawnien = $entityManager
                               ->getRepository(Entry::class)
                               ->findZmianyNaUzytkownikach();

        $ldapService = $this->get('ldap_service');
        $zbiorZmian = $this->pokazDlaWszystkich($zmianyUprawnien);

        if (!empty($departament)) {
            $uzytkownicy = $ldapService->getUsersFromOU($departament);

            if (null === $uzytkownicy) {
                throw new \Exception('Nie znaleziono takiego departamentu.');
            }

            $uzytkownicyOuNazwy = array();
            foreach ($uzytkownicy as $uzytkownik) {
                $uzytkownicyOuNazwy[] = $uzytkownik['samaccountname'];
            }

            $zbiorZmian = $this->odfiltrujDlaDepartamentu($zbiorZmian, $uzytkownicyOuNazwy);
        }

        if ('html' === $format) {
            return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $zbiorZmian]);
        }

        if ($returnArray) {
            return $zbiorZmian;
        }

        return new JsonResponse($zbiorZmian);
    }

    /**
     * Odbiera lub anuluje administracyjnie wnioski przed określoną
     * datą dla konkretnego użytkownika.
     *
     * @param string $nazwaUzytkownika
     * @param DateTime $dataGraniczna
     *
     * @Route("/anulujodbierzadministracyjnie/{nazwaUzytkownika}/{dataGraniczna}", name="anuluj_odbierz_administracyjnie")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @return JsonResponse
     */
    public function anulujOdbierzAdministracyjnieAction($nazwaUzytkownika, DateTime $dataGraniczna)
    {
        $uprawnieniaService = $this->get('uprawnienia_service');

        if (null !== $nazwaUzytkownika && null !== $dataGraniczna) {
            $uprawnieniaService
                ->odbierzZasobyUzytkownikaOdDaty($nazwaUzytkownika, $dataGraniczna);

                return new JsonResponse([1]);
        }

        return new JsonResponse([0]);
    }

    /**
     * Odbiera lub anuluje administracyjnie wnioski przed datą graniczną.
     * Czyli wnioski złożone przed np. zmianą departamentu.
     *
     * @param string $skrotDepartamentu
     *
     * @Route(
     *  "/anulujodbierzadministracyjniedepartament/{skrotDepartamentu}",
     *  name="anuluj_odbierz_administracyjnie_departament"
     * )
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @return JsonResponse
     */
    public function anulujOdbierzAdministracyjnieDepartamentAction($skrotDepartamentu)
    {
        ini_set('max_execution_time', 0);
        $zbiorZmian = $this->przegladZmianNaKoncieAction($skrotDepartamentu, true);

        $uprawnieniaService = $this->get('uprawnienia_service');
        $errors = [];

        foreach ($zbiorZmian as $klucz => $zmiana) {
            try {
                $uprawnieniaService
                ->odbierzZasobyUzytkownikaOdDaty($klucz, new DateTime($zmiana['ostatnia_zmiana']), $zmiana['powod']);
            } catch (Exception $exception) {
                $errors[] = $klucz;
            }
        }

        return new JsonResponse($errors);
    }


    /**
     * Usuwa z tablicy osoby które nie są w tablicy użytkowników
     * z danego departamentu.
     *
     * @param array $zmiany
     * @param array $listaUzytkownikow
     *
     * @return array
     */
    public function odfiltrujDlaDepartamentu(array $zmiany, array $listaUzytkownikow)
    {
        foreach ($zmiany as $key => $zmiana) {
            if (!in_array($key, $listaUzytkownikow)) {
                unset($zmiany[$key]);
            }
        }

        return $zmiany;
    }

    /**
     * Zwraca tylko wpisy gdzie wystąpiło zdarzenie
     * zgodnie z Redmine #58977
     * Zwraca użytkowników ze wszystkich departametnow
     * $zmianyUprawnien zawiera listę wszystkich zmian.
     *
     * @param array $zmianyUprawnien
     *
     * @return array
     */
    public function pokazDlaWszystkich(array $zmianyUprawnien)
    {
        $zmiany = array();

        foreach ($zmianyUprawnien as $zmiana) {
            if (null !== $zmiana->getFromWhen()) {
                $dataZmiany = $zmiana->getFromWhen()->format('Y-m-d H:i:s');
            } else {
                $dataZmiany = null;
            }

            $nazwaKonta = $zmiana->getSamaccountName();
            $info = $zmiana->getInfo();
            $division = $zmiana->getDivision();
            $title = $zmiana->getTitle();
            $distinguishedName = $zmiana->getDistinguishedName();

            if (!isset($zmiany[$nazwaKonta])) {
                $zmiany[$nazwaKonta]['ostatnia_zmiana'] = null;
                $zmiany[$nazwaKonta]['ou'] = $distinguishedName;
            }

            $zmianaStatus = false;

            if (null !== $title) {
                if ($dataZmiany > $zmiany[$nazwaKonta]['ostatnia_zmiana']) {
                    $zmianaStanowiska = $this->sprawdzZmianeStanowiska($zmiana);
                    if (null !== $zmianaStanowiska) {
                        $zmiany[$nazwaKonta]['ostatnia_zmiana'] = $dataZmiany;
                        $zmiany[$nazwaKonta]['powod'] = 'TITLE';
                        $zmianaStatus = true;
                    }
                }
            }

            if ((null !== $division && null !== $info) || (null == $division && null !== $info)) {
                if ($dataZmiany > $zmiany[$nazwaKonta]['ostatnia_zmiana']) {
                    $zmiany[$nazwaKonta]['ostatnia_zmiana'] = $dataZmiany;
                    $zmiany[$nazwaKonta]['powod'] = 'DIVISION/INFO';
                    $zmianaStatus = true;
                }
            }

            if (null !== $distinguishedName) {
                if ($distinguishedName !== $zmiany[$nazwaKonta]['ou']) {
                    $zmiany[$nazwaKonta]['ou'] = $distinguishedName;
                    if ($dataZmiany > $zmiany[$nazwaKonta]['ostatnia_zmiana']) {
                        $zmiany[$nazwaKonta]['ostatnia_zmiana'] = $dataZmiany;
                        $zmiany[$nazwaKonta]['powod'] = 'OU';
                        $zmianaStatus = true;
                    }
                }
            }

            if (null === $zmiany[$nazwaKonta]['ostatnia_zmiana']) {
                unset($zmiany[$nazwaKonta]);
            }
        }

        return $zmiany;
    }

    /**
     * Sprawdza czy zaszła zmiana stanowiska na określoną.
     * Jeżeli tak to zwraca datę tej zmiany.
     *
     * @param Entry $zmiana
     *
     * @return null|Datetime
     */
    public function sprawdzZmianeStanowiska(Entry $zmiana)
    {
        $stanowiska = array(
            'kierownik',
            'koordynator',
            'zastępca dyrektora',
            'zastępca prezesa',
            'prezes',
            'dyrektor',
        );

        if (in_array($zmiana->getTitle(), $stanowiska)) {
            return $zmiana->getFromWhen();
        }

        return null;
    }

    /**
     * Na podstawie loginu użytkownika oraz daty, zwraca wnioski
     * które były złożone przed i po tej dacie.
     *
     * @Route(
     *  "/przegladUprawnien/znajdzWnioski/{user}/{date}",
     *  name="przeglad_uprawnien_znajdz_wnioski"
     * )
     *
     * @param string $user
     * @param string $date
     *
     * @return JsonResponse
     */
    public function findWnioskiByDateAction($user, $date)
    {
        $zasobyService = $this->get('zasoby_service');

        $date = new DateTime($date);
        $wnioskiByDate = $zasobyService->findAktywneWnioski($user, $date);

        return new JsonResponse($wnioskiByDate);
    }

    /**
     * @Route("/kombajnAnulowaniaPrzedData/{nazwaUzytkownika}/{dataGraniczna}/{wprowadzZmiany}", name="kombajn_anulowania")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     */
    public function jednorazowyKombajnAnulowaniaWnioskowPrzedData(string $nazwaUzytkownika, DateTime $dataGraniczna, bool $wprowadzZmiany = false)
    {
        $zasobyService = $this->get('zasoby_service');

        /**
         * Tablica zawierajaca liste UserZasoby (id user_zasob, id zasob, id_wniosekNadanieOdebranie)
         * złożone przed i po dacie.
         * Klucze: ['przed_data'], ['po_dacie']
         *
         * @var array
         */
        $wnioskiPoDacie = $zasobyService->findAktywneWnioski($nazwaUzytkownika, $dataGraniczna);

        $uprawnieniaService = $this->get('uprawnienia_service');
        $uprawnieniaService->setWypchnijEntryPrzyAnulowaniu(false);

        $zasobyZGrupamiAd = $uprawnieniaService->pobierzZasobyIdZGrupamiAd();

        $przeprocesowane = $uprawnieniaService->odbierzZasobyUzytkownikaOdDaty($nazwaUzytkownika, $dataGraniczna, 'Odebrano z powodu zmiany departamentu/sekcji/stanowiska.', false, true);


        $zasobyService = $this->get('zasoby_service');

        $przeprocesowaneBezGrup = [];
        $przeprocesowaneZGrupami = [];
        foreach ($przeprocesowane as $jedenZasob) {
            if (in_array($jedenZasob['zasob'], $zasobyZGrupamiAd)) {
                $przeprocesowaneZGrupami[] = $jedenZasob;
            } else {
                $przeprocesowaneBezGrup[] = $jedenZasob;
            }
        }

        VarDumper::dump(['Odebrane administracyjnie z grupami AD' => $przeprocesowaneZGrupami]);

        $this->wyslijInfoDoAdministratorow($nazwaUzytkownika, $przeprocesowaneBezGrup, $dataGraniczna);

        if (isset($wnioskiPoDacie[$nazwaUzytkownika]['po_dacie'])) {
            $this->wyzerujGrupyAdNadajNowePodstawowe($wnioskiPoDacie[$nazwaUzytkownika]['po_dacie'], $nazwaUzytkownika);
        }

        if ($wprowadzZmiany) {
            $this->getDoctrine()->getManager()->flush();
        }

        VarDumper::dump($this->logWpis);
        die;
    }

    /**
     * @Route("/jednorazoweAnulowanieDlaNieobecnych/{nazwaUzytkownika}/{wprowadzZmiany}", name="jednorazowe_anulowanie_dla_nieobecnych")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     */
    public function jednorazoweAnulowanieDlaNieobecnych(string $nazwaUzytkownika, bool $wprowadzZmiany = false)
    {
        $dataGraniczna = new DateTime();
        $czyDodacOdebranieZasobowEntry = false;
        $powodOdebrania = WyzwalaczeConstants::DLUGOTRWALA_NIEOBECNOSC_TITLE;
        $atrybut = 'description';
        $nowaWartosc = 'Konto wyłączono z powodu nieobecności dłuższej niż 30 dni';

        $uprawnieniaService = $this->get('uprawnienia_service');
        $uprawnieniaService->setWypchnijEntryPrzyAnulowaniu(false);
        $zasobyZGrupamiAd = $uprawnieniaService->pobierzZasobyIdZGrupamiAd();
        $przeprocesowane = $uprawnieniaService->odbierzZasobyUzytkownikaOdDaty($nazwaUzytkownika, $dataGraniczna, $powodOdebrania, false, true);

        $przeprocesowaneBezGrup = [];
        $przeprocesowaneZGrupami = [];
        if (is_array($przeprocesowane) && !empty($przeprocesowane)) {
            foreach ($przeprocesowane as $jedenZasob) {
                if (in_array($jedenZasob['zasob'], $zasobyZGrupamiAd)) {
                    $przeprocesowaneZGrupami[] = $jedenZasob;
                } else {
                    $przeprocesowaneBezGrup[] = $jedenZasob;
                }
            }
        }

        VarDumper::dump(['Odebrane administracyjnie z grupami AD' => $przeprocesowaneZGrupami]);
        VarDumper::dump(['Odebrane administracyjnie bez grup' => $przeprocesowaneBezGrup]);

        $uprawnieniaService->wyslijInfoDoAdministratorow($nazwaUzytkownika, $przeprocesowaneBezGrup, $dataGraniczna, $powodOdebrania);

        $ldapService = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapconn = $ldapAdmin->prepareConnection();

        $adUser = $ldapService->getUserFromAD($nazwaUzytkownika, null, null, 'nieobecni')[0];
        $ldapAdmin->ldapModify($ldapconn, $adUser['distinguishedname'], [$atrybut => $nowaWartosc]);

        $wszystkiePosiadaneDoUsuniecia = $adUser['memberOf'];

        VarDumper::dump(['Grupy do usuniecia' => $wszystkiePosiadaneDoUsuniecia]);

        $entityManager = $this->getDoctrine()->getManager();
        if (!empty($wszystkiePosiadaneDoUsuniecia)) {
            $entry = new Entry();
            $entry
                ->setSamaccountname($nazwaUzytkownika)
                ->setIsDisabled(true)
                ->setCreatedBy('SYSTEM')
                ->setOpis($powodOdebrania)
                ->setMemberOf('-' . implode(',-', $wszystkiePosiadaneDoUsuniecia))
                ->setCreatedAt(new DateTime())
                ->setDisableDescription(AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC)
                ->setFromWhen(new DateTime());
            $entityManager->persist($entry);

            if ($czyDodacOdebranieZasobowEntry) {
                $odebranieZasobowEntry = new OdebranieZasobowEntry();
                $odebranieZasobowEntry
                    ->setPowodOdebrania($powodOdebrania)
                    ->setUzytkownik($nazwaUzytkownika);

                $entityManager->persist($odebranieZasobowEntry);

                $entry->setOdebranieZasobowEntry($odebranieZasobowEntry);
                $entityManager->persist($entry);
            }

            $this->logWpis[$nazwaUzytkownika]['usunieto_grupy_ad'] = $wszystkiePosiadaneDoUsuniecia;
        }

        if ($wprowadzZmiany) {
            $this->getDoctrine()->getManager()->flush();
        }

        VarDumper::dump($this->logWpis);
        die();
    }


    /**
     * Trzeba tylko zrobić żeby zerowało mu grupy i nadało z tych wniosków
     *
     * @param array $doNadaniaAd
     * @param string $nazwaUzytkownika
     *
     * @return void
     */
    private function wyzerujGrupyAdNadajNowePodstawowe($doNadaniaAd, $nazwaUzytkownika)
    {
        $ldapService = $this->get('ldap_service');
        $adUser = $ldapService->getUserFromAD($nazwaUzytkownika)[0];
        $wszystkiePosiadaneDoUsuniecia = $adUser['memberOf'];
        $entityManager = $this->getDoctrine()->getManager();
        if (!empty($wszystkiePosiadaneDoUsuniecia)) {
            $entry = new Entry();
            $entry
                ->setSamaccountname($nazwaUzytkownika)
                ->setMemberOf('-' . implode(',-', $wszystkiePosiadaneDoUsuniecia))
                ->setFromWhen(new DateTime())
            ;
            $entityManager->persist($entry);
            $this->logWpis[$nazwaUzytkownika]['usunieto_podstawowe'] = $wszystkiePosiadaneDoUsuniecia;
        }
        $podstawoweUprawnienia = $ldapService->getGrupyUsera($adUser, $adUser['description'], $adUser['division']);

        if (!empty($podstawoweUprawnienia)) {
            $entry = new Entry();
            $entry
                ->setSamaccountname($nazwaUzytkownika)
                ->setMemberOf('+' . implode(',+', $podstawoweUprawnienia))
                ->setFromWhen(new DateTime())
            ;
            $entityManager->persist($entry);
            $this->logWpis[$nazwaUzytkownika]['nadano_podstawowe'] = $podstawoweUprawnienia;
        }

        $grupyDoNadania = [];

        $uprawnieniaService = $this->get('uprawnienia_service');
        foreach ($doNadaniaAd as $wpis) {
            if ($wpis['grupy_ad']) {
                $zasob = $entityManager
                    ->getRepository(Zasoby::class)
                    ->findOneById($wpis['zasob_id'])
                ;
                $userZasob = $entityManager
                    ->getRepository(UserZasoby::class)
                    ->findOneById($wpis['user_zasoby_id'])
                ;

                $grupyDoNadania[] = $uprawnieniaService->znajdzGrupeAD($userZasob, $zasob);
            }
        }

        if (!empty($grupyDoNadania)) {
            $this->logWpis[$nazwaUzytkownika]['grupy_z_wnioskow'] = $grupyDoNadania;
            $entry = new Entry();
            $entry
                ->setSamaccountname($nazwaUzytkownika)
                ->setMemberOf('+' . implode(',+', $grupyDoNadania))
                ->setFromWhen(new DateTime())
            ;

            $entityManager->persist($entry);
        }
    }
    /**
     * Wysyła mail do adminów zasobów żeby przejrzeli użytkowników.
     *
     * @param string $nazwaUzytkownika
     * @param array $procesowaneZasoby
     * @param DateTime $dataZmiany
     *
     * @return void
     */
    private function wyslijInfoDoAdministratorow(string $nazwaUzytkownika, array $przeprocesowneZasoby, DateTime $dataZmiany)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $odebraneZasoby = [];
        foreach ($przeprocesowneZasoby as $zasob) {
            if (WniosekStatus::ODEBRANO_ADMINISTRACYJNIE  === $zasob['status']) {
                $userZasob = $entityManager
                    ->getRepository(UserZasoby::class)
                    ->findOneById($zasob['user_zasob'])
                ;

                $zasob = $entityManager
                    ->getRepository(Zasoby::class)
                    ->findOneById($zasob['zasob'])
                ;

                $odebraneZasoby[$zasob->getId()]['object'] = $zasob;
                $odebraneZasoby[$zasob->getId()]['modul'][] = $userZasob->getModul();
                $odebraneZasoby[$zasob->getId()]['poziom'][] = $userZasob->getPoziomDostepu();
                $odebraneZasoby[$zasob->getId()]['poziom_modul'][] = ['modul' => $userZasob->getModul(), 'poziom' => $userZasob->getPoziomDostepu()];
            }
        }

        $mailer = $this->get('parp.mailer');
        $mailer->disableFlush();
        foreach ($odebraneZasoby as $odebrany) {
            $odebrany['odbiorcy'] = [$mailer->getUserMail($odebrany['object']->getAdministratorZasobu())];
            $odebrany['imie_nazwisko'] = $odebrany['object']->getAdministratorZasobu();
            $odebrany['login'] = $odebrany['object']->getAdministratorZasobu();
            $odebrany['dotyczy'] = $nazwaUzytkownika;
            $odebrany['data_zmiany'] = $dataZmiany;
            $mailer->sendEmailByType(ParpMailerService::TEMPLATE_ODEBRANIE_UPRAWNIEN__JEDNORAZOWY, $odebrany);
        }

        $this->logWpis[$nazwaUzytkownika]['wyslano_mail_do_admina'] = $odebraneZasoby;
    }
}
