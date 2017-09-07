<?php

namespace ParpV1\MainBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Exception\SecurityTestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * RaportyIT controller.
 * @Security("has_role('PARP_ADMIN')")
 * @Route("/RaportyIT")
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
                    'class' => 'col-sm-4 control-label',
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
                'class' => 'col-sm-4 control-label',
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
                $ndata, $this->get('ldap_service'), $this->getDoctrine()->getManager(), $this->get('samaccountname_generator')
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
        $data = new \DateTime();
        $rok = null === $rok ? $data->format('Y') : $rok;
        $miesiac = null === $miesiac ? $data->format('m') : $miesiac;

        if ($miesiac < 1 || $miesiac > 12) {
            throw new InvalidArgumentException('Miesiąc musi być z zakresu 1 <> 12');
        }

        if ($rok < 2006 || $rok > 2025) {
            throw new InvalidArgumentException('Rok musi być z zakresu 2006 <> 2025');
        }

        $raport = $this->generujRaport(
            ['rok' => $rok, 'miesiac' => $miesiac], $this->get('ldap_service'), $this->getDoctrine()->getManager(), $this->get('samaccountname_generator')
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

        $repo = $em->getRepository('ParpMainBundle:DaneRekord');
        $historyRepo = $em->getRepository('ParpV1\MainBundle\Entity\HistoriaWersji');
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
                        $em->getRepository('ParpV1\MainBundle\Entity\Departament')
                            ->findOneByNameInRekord($wpis->getDepartament());
                    $dep2 =
                        $em->getRepository('ParpV1\MainBundle\Entity\Departament')
                            ->findOneByNameInRekord($wpisNowy->getDepartament());
                    $akcja = "Zmiana departamentu z '".$dep1->getName()."' na '".$dep2->getName()."'";
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
                $dataExpire = \DateTime::createFromFormat('Y-m-d', $u['accountExpires']);

                if ($rok == date('Y')) {
                    if ($rok < 3000 && $dataExpire->format('Y-m') == $ndata['rok'].'-'.$miesiac) {
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
            if ($dr['umowaOd']->format('Y-m') == $rok.'-'.$miesiac) {
                $akcja = 'Nowa osoba przyszła do pracy';
                $dataZmiany = $dr['umowaOd']->format('Y-m-d');
            } else {
                if ($dr['umowaDo'] && $dr['umowaDo']->format('Y-m') == $rok.'-'.$miesiac) {
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
        return $em->getRepository('ParpMainBundle:DaneRekord')->findChangesInMonth($rok, $miesiac);
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
                    'id'        => \uniqid().$l.''.$log->getId(),
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
                    $dana['id'] = $dana['id'].md5($content);
                    $dana['type'] = 'background';
                    $dana['className'] = 'tloWykresu'.$this->className++;
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
        $now = new \Datetime();
        $datyKonca = [
            'departament' => $now,
            'sekcja'      => $now,
            'stanowisko'  => $now,
        ];
        $em = $this->container->getDoctrine()->getManager();

        $this->user = $this->container->get('ldap_service')->getUserFromAD($login, null, null, 'wszyscyWszyscy')[0];


        $this->departamenty = $em->getRepository('ParpMainBundle:Departament')->bierzDepartamentyNowe();
        //pobierz dane zmiany departamentow, stanowisk
        $entity = $em->getRepository('ParpMainBundle:DaneRekord')->findOneByLogin($login);

        if ($entity === null) {
            $min = new \DateTime('2001-01-01 00:00:00');
            $max = new \DateTime('2101-01-01 00:00:00');
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
            $repo = $em->getRepository('ParpV1\MainBundle\Entity\HistoriaWersji'); // we use default log entry class
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
            $uzs = $em->getRepository('ParpMainBundle:UserZasoby')->findDlaOsoby($login, $d['start'], $d['end']);
            foreach ($uzs as $uz) {
                $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($uz->getZasobId());
                $do =
                    $uz->getAktywneDo() ? ($uz->getAktywneDo() <
                    $d['end'] ? $uz->getAktywneDo() : $d['end']) : $d['end'];
                $c =
                    ' <a href="'.
                    $this->generateUrl('zasoby_edit', ['id' => $zasob->getId()]).
                    '">'.
                    $zasob->getNazwa().
                    '</a>';
                $dana = [
                    'id'         => \uniqid().'Zasob'.$zasob->getId(),
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
        $now = new \Datetime();
        usort($dane, function ($a, $b) {
            return $a['start'] < $b['start'];
        });
        for ($i = 0; $i < count($dane); $i++) {
            if ($dane[$i]['start'] >= $this->ostatniDepartament['start'] &&
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

        $content = $this->sumaUprawnien['content'].': <br><br>';
        $this->sumaUprawnien['brakujace'] = [];
        foreach ($this->sumaUprawnien['grupy'] as $g) {
            if (!in_array($g, $this->user['memberOf'], true)) {
                $content .= '<span style="color:red"><b>'.$g.'</b></span><br>';
                $this->sumaUprawnien['brakujace'][] = $g;
            } else {
                $content .= $g.'<br>';
            }
        }
        $content .= '<br><a href="'.
            $this->generateUrl(
                'nadajGrupy',
                ['login' => $this->user['samaccountname'], 'grupy' => implode(',', $this->sumaUprawnien['grupy'])]
            ).
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
    public function nadajGrupyAction($login = 'kamil_jakacki', $grupy = '')
    {
        $grupy = explode(',', $grupy);
        $entry = new Entry();
        $entry->setFromWhen(new \Datetime());
        $entry->setSamaccountname($login);
        $entry->setMemberOf('+'.implode(',+', $grupy));
        $entry->setCreatedBy($this->getUser()->getUsername());
        $entry->setOpis('Przywracanie uprawnien za pomoca linku.');
        $this->getDoctrine()->getManager()->persist($entry);
        $this->getDoctrine()->getManager()->flush();
        var_dump($login, $grupy, $entry);
        //die();
    }
}
