<?php

namespace ParpV1\MainBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\Uprawnienia;
use ParpV1\MainBundle\Entity\GrupyUprawnien;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Form\UserZasobyType;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Exception\SecurityTestException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class NadawanieUprawnienZasobowController extends Controller
{

    protected function generateRemoveAccessChoices($sams)
    {
        //tu zwracamy labelki z separatorem @@@ ktory pokazuje kolejne kolumny (osoba, od, do , modul, funkcja)
        $choices = [];
        $userzasoby = array();
        $userzasobyOpisy = array();
        $zasobyOpisy = array();
        $ids = array();
        $uzs = $this->getDoctrine()->getRepository(UserZasoby::class)->findBy(array('samaccountname' => $sams, 'czyAktywne' => true, 'wniosekOdebranie' => null, 'czyOdebrane' => false /*  'czyNadane' => true */));
        // tu trzeba przerobic y kluczem byl id UserZasoby a nie Zasoby bo jeden user moze miec kilka pozopmiw dostepu i kazdy mozemy odebrac oddzielnie
        foreach ($uzs as $uu) {
            if (!in_array($uu->getZasobId(), $ids, true)) {
                $ids[] = $uu->getZasobId();
            }
            $userzasoby[$uu->getId()] = $uu;
            $userzasobyOpisy[$uu->getId()] = $uu->getOpisHtml();//nieuzywane
        }
        $chs = $this->getDoctrine()->getRepository(Zasoby::class)->findById($ids);
        foreach ($chs as $ch) {
            $zasobyOpisy[$ch->getId()] = $ch;
        }
        foreach ($userzasoby as $uzid => $uz) {
            $moduly = explode(';', $uz->getModul());
            foreach ($moduly as $modul) {
                $poziomy = explode(';', $uz->getPoziomDostepu());
                foreach ($poziomy as $poziom) {
                    $data = [
                        $zasobyOpisy[$uz->getZasobId()],
                        $uz->getSamaccountname(),
                        $uz->getAktywneOd()->format('Y-m-d').' - '.($uz->getAktywneDo() ? $uz->getAktywneDo()->format('Y-m-d') : '*'),
                        $modul,
                        $poziom
                    ];
                    $klucz = $uzid.';'.$modul.';'.$poziom;
                    $choices[$klucz] = implode('@@@', $data);
                }
            }
        }


        return $choices;
    }
    /**
     * @param $samaccountName
     * @Route("/addRemoveAccessToUsersAction/{action}/{wniosekId}", name="addRemoveAccessToUsersAction", defaults={"wniosekId" : 0})
     */
    public function addRemoveAccessToUsersAction(Request $request, $action, $wniosekId = 0)
    {
        $zasobSpecjalnyUprawnienie = in_array("PARP_ZASOBY_SPECJALNE", $this->getUser()->getRoles());
        $zasobyId = '';
        if ($request->getMethod() == 'POST') {
            //\Doctrine\Common\Util\Debug::dump($request->get('form')['samaccountnames']);die();
            $samaccountnames = $request->get('form')['samaccountnames'];
        } else {
            $samaccountnames = $request->get('samaccountnames');
            $zasobyId = $request->get('zasobyId');
        }
        //var_dump($samaccountnames); die('addRemoveAccessToUsersAction - mam tamten controller');
        $wniosek = $this->getDoctrine()->getRepository(WniosekNadanieOdebranieZasobow::class)->find($wniosekId);

        if (null !== $wniosek) {
            if ($wniosek->getWniosek()->getIsBlocked()) {
                throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
            }
        }

        $samt = json_decode($samaccountnames);
        //print_r($samaccountnames);
        if ($samt == '') {
            $this->addFlash('warning', 'Nie można znaleźć wybranych użytkowników!');
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array()));
        }
        //print_r($samt); die();
        $sams = array();
        foreach ($samt as $k => $v) {
            if ($v == 1) {
                $sams[] = $k;
            }
        }


        switch ($action) {
            case 'addResources':
                $title = 'Wybierz zasoby do dodania';
                $userzasoby = array();
                $userzasobyOpisy = array();
                $ids = array();
                $uzs = $this->getDoctrine()->getRepository(UserZasoby::class)->findBy(array('samaccountname' => $sams, 'czyAktywne' => true, 'czyNadane' => true));
                foreach ($uzs as $uu) {
                    if (!in_array($uu->getZasobId(), $ids, true)) {
                        $ids[] = $uu->getZasobId();
                    }
                    $userzasoby[$uu->getZasobId()][] = $uu->getSamaccountname();
                    $userzasobyOpisy[$uu->getZasobId()][$uu->getSamaccountname()] = $uu->getOpisHtml();
                }
                $chsTemp = $this->getDoctrine()->getRepository(Zasoby::class)->findByPublished(1);

                if (!$wniosekId && !in_array('PARP_ADMIN', $this->getUser()->getRoles(), true)) {
                    $chs = [];
                    $login = $this->getUser()->getUsername();
                    foreach ($chsTemp as $zasob) {
                        $admini = array_merge(explode(',', $zasob->getAdministratorZasobu()), explode(',', $zasob->getAdministratorTechnicznyZasobu()));
                        //echo ".".$zasob->getAdministratorZasobu().".";
                        if (in_array($login, $admini, true)) {
                            $chs[] = $zasob;
                        }
                    }
                } else {
                    $chs = array();

                    foreach ($chsTemp as $zasob) {
                        if (true !== $zasob->getZasobSpecjalny()) {
                            $chs[] = $zasob;
                        } elseif (true === $zasob->getZasobSpecjalny()) {
                            if (true === $zasobSpecjalnyUprawnienie
                                || in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $this->getUser()->getRoles())
                                || $this->dostepDoZasobowSpecjalnych($zasob)) {
                                $chs[] = $zasob;
                            }
                        }
                    }
                }
                //var_dump($chs); die();
                break;
            case 'removeResources':
                $title = 'Odbierz zasoby';
                $choices = $this->generateRemoveAccessChoices($sams);
                //$chs = $this->getDoctrine()->getRepository(Zasoby::class)->findBySamaccountnames($sams);
                break;
            case 'editResources':
                //tu pobierze userzasobId wczyta go i postem odbije
                $uzid = $request->get('uzid');
                $uz = $this->getDoctrine()->getRepository(UserZasoby::class)->find($uzid);

                if (null === $uz) {
                    throw new EntityNotFoundException('Nie ma zasobu o id '.$uzid);
                }

                $ndata = array(
                    'samaccountnames' => $uz->getSamaccountnames(),
                    'wniosekId' => $wniosekId,
                    'action' => 'editResources',
                    'fromWhen' => $uz->getAktywneOd()->format('Y-m-d'),
                    'powod' => $uz->getPowodNadania(),
                    'nazwafiltr' => '',
                    'grupy' => '',
                    'access' => array($uz->getZasobId()),
                );

                return $this->addResourcesToUsersAction($request, $ndata, $wniosekId, $uzid, $uz);

                break;
            case 'addPrivileges':
                $title = 'Wybierz uprawnienia do dodania';

                $uzs = $this->getDoctrine()->getRepository(UserUprawnienia::class)->findBy(array('samaccountname' => $sams, 'czyAktywne' => true));
                $ids = array();
                $useruprawnienia = array();
                foreach ($uzs as $uu) {
                    if (!in_array($uu->getUprawnienieId(), $ids, true)) {
                        $ids[] = $uu->getUprawnienieId();
                    }
                    $useruprawnienia[$uu->getUprawnienieId()][] = $uu->getSamaccountname();
                }
                $chs = $this->getDoctrine()->getRepository(Uprawnienia::class)->findall();//ById($ids);
                break;
            case 'removePrivileges':
                $title = 'Wybierz uprawnienia do odebrania';
                $uzs = $this->getDoctrine()->getRepository(UserUprawnienia::class)->findBy(array('samaccountname' => $sams, 'czyAktywne' => true));
                $ids = array();
                $useruprawnienia = array();
                foreach ($uzs as $uu) {
                    if (!in_array($uu->getUprawnienieId(), $ids, true)) {
                        $ids[] = $uu->getUprawnienieId();
                    }
                    $useruprawnienia[$uu->getUprawnienieId()][] = $uu->getSamaccountname();
                }
                $chs = $this->getDoctrine()->getRepository(Uprawnienia::class)->findById($ids);
                break;
        }

        $choicesDescription = array();//niwuzywane

        if ($action != 'removeResources') {
            $choices = array();
            foreach ($chs as $ch) {
                if ($action == 'addResources') {
                    $info = count($sams) > 1 ? 'Nie posiadają' : 'Nie posiada';
                    if (isset($userzasoby[$ch->getId()]) && count($userzasoby[$ch->getId()]) > 0) {
                        $ret = array();
                        foreach ($userzasoby[$ch->getId()] as $u) {
                            $ret[] = $u;//"<span data-toggle='popover' data-content='".$userzasobyOpisy[$ch->getId()][$u]."'>".$u."</span>";
                        }


                        //$uss = implode(",", $userzasoby[$ch->getId()]);
                        $info = (count($userzasoby[$ch->getId()]) > 1 ? 'Posiadają : ' : 'Posiada : ').implode(' ', $ret);
                    }

                    if ($wniosekId == 0) {
                        $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                    } else {
                        if ($wniosek->getWniosek()->getStatus()->getNazwaSystemowa() == '00_TWORZONY') {
                            $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                        } else {
                            //tylko jesli juz jest we wniosku
                            $jest = false;
                            foreach ($wniosek->getUserZasoby() as $uz) {
                                if ($uz->getZasobId() == $ch->getId()) {
                                    $jest = true;
                                }
                            }
                            if ($jest || $wniosek->getZasobId() == $ch->getId()) {
                                $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                            }
                        }
                    }
                } elseif ($action == 'addPrivileges' || $action == 'removePrivileges') {
                    //die(".".count($sams));
                    $info = count($sams) > 1 ? 'Nie posiadają' : 'Nie posiada';
                    if (isset($useruprawnienia[$ch->getId()]) && count($useruprawnienia[$ch->getId()]) > 0) {
                        $uss = implode(',', $useruprawnienia[$ch->getId()]);
                        $info = count($sams) > 1 ? 'Posiadają : '. (count($useruprawnienia[$ch->getId()]) == count($sams) ? 'WSZYSCY' : $uss) : 'Posiada';
                    }
                    $gids = array();
                    foreach ($ch->getGrupy() as $g) {
                        $gids[] = $g->getId();
                    }
                    //print_r($gids); die();
                    $choices[$ch->getId()] = $ch->getOpis();//."@@@".$info."@@@".implode(",", $gids);
                }
            }
        }

        return $this->addRemoveAccessToUsers($request, $samaccountnames, $choices, $title, $action, $wniosekId, $zasobyId);
    }

    /**
     * Sprawdza czy aktualny użytkownik może widzieć zasób specjalny.
     *
     * @param Zasoby $zasob
     */
    private function dostepDoZasobowSpecjalnych(Zasoby $zasob)
    {
        $listaPowiernikow = explode(',', $zasob->getPowiernicyWlascicielaZasobu());
        $listaWlascicieli = explode(',', $zasob->getWlascicielZasobu());
        $listaAdministratorow = explode(',', $zasob->getAdministratorZasobu());
        $listaAdministratorowTech = explode(',', $zasob->getAdministratorTechnicznyZasobu());
        $listaOsobUprawnionych = array_unique(
            array_merge(
                $listaPowiernikow,
                $listaWlascicieli,
                $listaAdministratorow,
                $listaAdministratorowTech
            )
        );

        if (in_array($this->getUser()->getUserName(), $listaOsobUprawnionych)) {
            return true;
        }

        return false;
    }

    protected function addRemoveAccessToUsers(
        Request $request,
        $samaccountnames,
        $choices,
        $title,
        $action,
        $wniosekId = 0,
        $zasobyId = ''
    ) {

        $wniosek = $this->getDoctrine()->getRepository(WniosekNadanieOdebranieZasobow::class)->find($wniosekId);
        //print_r($samaccountnames);
        $ldap = $this->get('ldap_service');
        $samaccountnames = json_decode($samaccountnames);
        $users = array();

        foreach ($samaccountnames as $sam => $v) {
            if ($v) {
                //echo " $sam ";
                if ($wniosek && $wniosek->getPracownikSpozaParp()) {
                    //$ADUser = $ldap->getUserFromAD($sam);
                    $users[] = array(
                        'samaccountname' => $sam,
                        'name' => $sam
                    );
                } else {
                    $ADUser = $ldap->getUserFromAD($sam, null, null, 'wszyscyWszyscy');
                    $users[] = $ADUser[0];
                }
            }
        }
        $grupys = $this->getDoctrine()->getRepository(GrupyUprawnien::class)->findAll();
        $grupy = array();
        foreach ($grupys as $g) {
            $grupy[$g->getId()] = $g->getOpis();
        }
        $now = new \Datetime();

        $builder = $this->createFormBuilder();
        $form = $builder
                ->add('samaccountnames', HiddenType::class, array(
                    'data' => $samaccountnames
                ))
                ->add('wniosekId', HiddenType::class, array(
                    'data' => $wniosekId
                ))
                ->add('action', HiddenType::class, array(
                    'data' => $action
                ))
                ->add('samaccountnames', HiddenType::class, array(
                    'required' => false,
                    'label' => 'Nazwa kont',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                        'readonly' => true,
                    ),
                    'data' => json_encode($samaccountnames)
                ))
                ->add('fromWhen', TextareaType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data zmiany',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label ',
                    ),
                    'required' => false,
                    'data' => $now->format('Y-m-d')
                ))
                ->add('powod', TextareaType::class, array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'label' => 'Cel nadania/odebrania',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => true
                ))
                ->add('grupy', ChoiceType::class, array(
                    'required' => false,
                    'label' => 'Filtruj po grupie uprawnień',
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left '.($action == 'addResources' || $action ==
                            'removeResources' ? 'hidden' : ''),
                    ),
                    'attr' => array(
                        'class' => 'ays-ignore '.($action == 'addResources' || $action == 'removeResources' ? 'hidden' : ''),
                    ),
                    'choices' => $grupy,
                    'multiple' => false,
                    'expanded' => false
                ))
                ->add('buttonzaznacz', ButtonType::class, array(
                    //'label' =>  false,
                    'attr' => array(
                        'class' => 'btn btn-info col-sm-12',
                    ),
                    'label' => 'Zaznacz wszystkie widoczne'
                ))
                ->add('buttonodznacz', ButtonType::class, array(
                    //'label' =>  false,
                    'attr' => array(
                        'class' => 'btn btn-info col-sm-12',
                    ),
                    'label' => 'Odznacz wszystkie'
                ))
                ->add('wybraneZasoby', TextareaType::class, array('mapped' => false, 'attr' => ['readonly' => true]))

                ->add('nazwafiltr', TextareaType::class, array(
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left ',
                    ),
                    'label' => 'Filtruj po nazwie',
                    'attr' => array(
                        'class' => 'ays-ignore ',
                    ),
                    'required' => false
                ))
                ->add('access', ChoiceType::class, array(
                    'required' => false,
                    'label' => $title,
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left uprawnienieRow',
                    ),
                    'attr' => array(
                        'class' => '',
                    ),
                    'choices' => array_flip($choices),
                    'multiple' => true,
                    'expanded' => true
                ))

                ->add('zapisz2', SubmitType::class, array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                    'label' => 'Dalej'
                ))
                ->add('zapisz', SubmitType::class, array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                    'label' => 'Dalej'
                ))
                ->setAction($this->generateUrl('addRemoveAccessToUsersAction', array('wniosekId' => $wniosekId, 'action' => $action)))
                ->setMethod('POST')
                ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();
            $sams = array();
            $s1 = json_decode($ndata['samaccountnames']);
            foreach ($s1 as $k => $v) {
                if ($v) {
                    $sams[] = $k;
                    $this->get('adcheck_service')->checkIfUserCanBeEdited($k);
                }
            }

            switch ($ndata['action']) {
                case 'addResources':
                    //check privileges - czy jest adminem zasobow
                    if (!$wniosekId) {
                        //jesli bez wniosku sprawdzamy czy jest PARP_ADMIN albo PARP_ADMIN_ZASOBU dla swoich zasobow
                        $this->sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($ndata['access']);
                    }


                    return $this->addResourcesToUsersAction($request, $ndata, $wniosekId);
                    break;

                case 'removeResources':
                    //check privileges - czy jest adminem zasobow!!!
                    if (!$wniosekId) {
                        //jesli bez wniosku sprawdzamy czy jest PARP_ADMIN albo PARP_ADMIN_ZASOBU dla swoich zasobow
                        $ids = [];
                        $zasobyName = [];
                        foreach ($ndata['access'] as $a) {
                            $ps = explode(';', $a);
                            $userZasob = $this->getDoctrine()->getManager()->getRepository(UserZasoby::class)->find($ps[0]);
                            $ids[] = $userZasob->getZasobId();
                            if (!in_array($userZasob->getZasobOpis(), $zasobyName)) {
                                $zasobyName = $userZasob->getZasobOpis();
                            }
                        }

                        $wniosek = $this->getDoctrine()->getRepository(WniosekNadanieOdebranieZasobow::class)->find($ndata['wniosekId']);
                        $wniosek->setZasoby(implode(',', $zasobyName));
                        $this->getDoctrine()->getManager()->persist($wniosek);
                        $this->sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($ids);
                    }

                    return $this->removeResourcesToUsersAction($request, $ndata, $wniosekId);

                    break;
                case 'addPrivileges':
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach ($ndata['access'] as $z) {
                        foreach ($sams as $currentsam) {
                            $zmianaupr = array();
                            $suz = $this->getDoctrine()->getManager()->getRepository(UserUprawnienia::class)->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));

                            $u = $this->getDoctrine()->getManager()->getRepository(Uprawnienia::class)->find($z);
                            if ($suz) {
                                $msg = 'NIE nadaje userowi '.$currentsam." uprawnienia  '".$u->getOpis()."' bo je ma !";
                                $this->addFlash('notice', $msg);
                            } else {
                                $suz = new UserUprawnienia();
                                $suz->setSamaccountname($currentsam);
                                $suz->setOpis($u->getOpis());
                                $suz->setDataNadania(new \Datetime($ndata['fromWhen']));
                                $suz->setDataOdebrania(null);
                                $suz->setCzyAktywne(true);
                                $suz->setUprawnienieId($z);
                                $suz->setPowodNadania($powod);
                                $this->getDoctrine()->getManager()->persist($suz);
                                //$this->getDoctrine()->getManager()->remove($suz);
                                $msg = 'Nadaje userowi '.$currentsam." uprawnienia  '".$u->getOpis()."' bo ich nie ma";
                                if ($u->getGrupaAd() != '') {
                                    $aduser = $this->get('ldap_service')->getUserFromAD($currentsam);
                                    $msg .= 'I dodaje do grupy AD : '.$u->getGrupaAd();
                                    $entry = new Entry($this->getUser()->getUsername());
                                    $entry->setFromWhen(new \Datetime());
                                    $entry->setSamaccountname($currentsam);
                                    $entry->setMemberOf('+'.$u->getGrupaAd());
                                    $entry->setIsImplemented(0);
                                    $entry->setDistinguishedName($aduser[0]['distinguishedname']);
                                    $this->getDoctrine()->getManager()->persist($entry);
                                }


                                $this->addFlash('warning', $msg);
                                $zmianaupr[] = $u->getOpis();
                            }

                            if (count($zmianaupr) > 0) {
                                $this->get('uprawnienia_service')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), array(), $zmianaupr);
                            }
                        }
                    }

                    if ($wniosek) {
                        $wniosek->ustawPoleZasoby();
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
                case 'removePrivileges':
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach ($ndata['access'] as $z) {
                        foreach ($sams as $currentsam) {
                            $zmianaupr = array();
                            $suz = $this->getDoctrine()->getManager()->getRepository(UserUprawnienia::class)->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));
                            if ($suz) {
                                $u = $this->getDoctrine()->getManager()->getRepository(Uprawnienia::class)->find($z);
                                $suz->setDataOdebrania(new \Datetime($ndata['fromWhen']));
                                $suz->setCzyAktywne(false);
                                $suz->setPowodOdebrania($powod);
                                //$this->getDoctrine()->getManager()->persist($suz);
                                $this->getDoctrine()->getManager()->remove($suz);
                                $msg = 'Odbieram userowi '.$currentsam." uprawnienia  '".$u->getOpis()."' bo je ma";

                                if ($u->getGrupaAd() != '') {
                                    $aduser = $this->get('ldap_service')->getUserFromAD($currentsam);
                                    $msg .= 'I wyjmuje z grupy AD : '.$u->getGrupaAd();
                                    $entry = new Entry($this->getUser()->getUsername());
                                    $entry->setFromWhen(new \Datetime());
                                    $entry->setSamaccountname($currentsam);
                                    $entry->setMemberOf('-'.$u->getGrupaAd());
                                    $entry->setIsImplemented(0);
                                    $entry->setDistinguishedName($aduser[0]['distinguishedname']);
                                    $this->getDoctrine()->getManager()->persist($entry);
                                }

                                $this->addFlash('warning', $msg);
                                $zmianaupr[] = $u->getOpis();
                            } else {
                                $msg = 'NIE odbieram userowi '.$currentsam." uprawnienia  '".$u->getOpis()."' bo ich nie ma !";
                                $this->addFlash('notice', $msg);
                            }

                            if (count($zmianaupr) > 0) {
                                $this->get('uprawnienia_service')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), $zmianaupr, array());
                            }
                        }
                    }

                    if ($wniosek) {
                        $wniosek->ustawPoleZasoby();
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
            }
        }
        //print_r($users);
        $tmpl = $wniosek ? 'ParpMainBundle:NadawanieUprawnienZasobow:addRemoveUserAccessByWniosek.html.twig' : 'ParpMainBundle:NadawanieUprawnienZasobow:addRemoveUserAccess.html.twig';
        return $this->render($tmpl, array(
            'wniosek' => $wniosek,
            'wniosekId' => $wniosekId,
            'included' => ($wniosek ? 1 : 0),
            'users' => $users,
            'form' => $form->createView() ,
            'title' => $title,
            'choicesDescription' => $choices,
            'action' => $action,
            'zasobyId' => $zasobyId
        ));
    }
    protected function sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($zids)
    {
        if (!in_array('PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW', $this->getUser()->getRoles(), true)) {
            die('Nie masz uprawnien by wykonywac ta akcje, musisz byc administratorem zasobow ze specjalna rola!');
        }
        $jestAdminemWszystkichZasobow = true;
        $username = $this->getUser()->getUsername();
        $nieJestAdminem = [];
        /** @var Zasoby[] $zasoby */
        $zasoby = $this->getDoctrine()->getRepository(Zasoby::class)->findById($zids);

        foreach ($zasoby as $zasob) {
            $admins = array_merge(
                explode(',', $zasob->getAdministratorZasobu()),
                explode(',', $zasob->getAdministratorTechnicznyZasobu())
            );

            $jestAdminemWszystkichZasobow = $jestAdminemWszystkichZasobow && in_array($username, $admins, true);
            if (!in_array($username, $admins, true)) {
                $nieJestAdminem[] = $zasob->getNazwa();
            }
        }

        if (!$jestAdminemWszystkichZasobow && !in_array('PARP_ADMIN', $this->getUser()->getRoles(), true)) {
            $msg = 'Nie możesz dodawać/odejmować uprawnień do zasbów których nie jesteś administratorem!!!';
            $msg .= "\r\n<br>Nie jesteś administratorem tych zasobów: ".implode(', ', $nieJestAdminem);
            die($msg);
        }
    }

    protected function removeResourcesToUsersAction(Request $request, $ndata = null, $wniosekId = 0, $uzid = 0, $userzasob = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        if (null !== $ndata) {
            $odbieranieUprawnienService = $this->get('odbieranie_uprawnien_service');
            $odbierzUprawnienia = $odbieranieUprawnienService
                ->odbierzZasobyUzytkownika($ndata['access'], $wniosekId, $ndata['powod']);

            $entityManager->flush();

            if ($odbierzUprawnienia) {
                return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
            }
        }

        $this->addFlash('danger', 'Odnotowałem odebranie wskazanych uprawnień.');

        return $this->redirect($this->generateUrl('main'));
    }

    /**
     * @param $samaccountName
     * @Route("/addResourcesToUsers/", name="addResourcesToUsers")
     */

    public function addResourcesToUsersAction(Request $request, $ndata = null, $wniosekId = 0, $uzid = 0, $userzasob = null)
    {
        $wniosek = $this->getDoctrine()->getRepository(WniosekNadanieOdebranieZasobow::class)->find($wniosekId);
//        print_r($ndata); die();
        $action = $uzid == 0 ? 'addResources' : 'editResources';
        $samaccountnamesPars = array(
            'required' => false,
            'label' => 'Nazwa kont',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
                'readonly' => true,
            )
        );
        $fromWhenPars = array(
            'attr' => array(
                'class' => 'form-control',
            ),
//                'widget' => 'single_text',
            'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'required' => false
        );
        $powodPars = array(
            'attr' => array(
                'class' => 'form-control',
            ),
            'label' => 'Powód nadania/odebrania',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'required' => true
        );
        $userzasoby = array();

        $choicesPoziomDostepu = array();
        $choicesModul = array();
        $now = new \Datetime();
        if ($ndata == null) {
            //die('mam nulla');
            $zids = array();
            //print_r($_POST['form']['userzasoby']);
            //$ndata2 = $form->getData();
            foreach ($_POST['form']['userzasoby'] as $v) {
                $zids[] = $v['zasobId'];
            }
            $fromWhenPars['data'] = $now->format('Y-m-d');
        } else {
            $samaccountnames = json_decode($ndata['samaccountnames']);
            $ldap = $this->get('ldap_service');
            $users = array();
            foreach ($samaccountnames as $sam => $v) {
                if ($v) {
                    if ($wniosek && $wniosek->getPracownikSpozaParp()) {
                        //$ADUser = $ldap->getUserFromAD($sam);
                        $users[] = array(
                            'samaccountname' => $sam,
                            'name' => $sam
                        );
                    } else {
                        $ADUser = $ldap->getUserFromAD($sam);
                        $users[] = $ADUser[0];
                    }
                }
            }
            $samaccountnamesPars['data'] = json_encode($samaccountnames);
            $fromWhenPars['data'] = $ndata['fromWhen'];
            $zids = $ndata['access'];
            $powodPars['data'] = $ndata['powod'];
            //$userzasobyPars['data'] = array();//$userzasoby;
        }
        $datauz = array(
            'aktywneOd' => $fromWhenPars['data'],
        );
        foreach ($zids as $v) {
            //print_r($v);
            $z = $this->getDoctrine()->getRepository(Zasoby::class)->find($v);




            //echo ".".count($z->getUzytkownicy()).".";
            if ($uzid == 0) {
                $uz = new UserZasoby();
            } else {
                $uz = $userzasob;

                $datauz['aktywneDo'] = $userzasob->getAktywneDo();
                $datauz['modul'] = $userzasob->getModul();
                $datauz['poziomDostepu'] = $userzasob->getPoziomDostepu();
            }
            $uz->setZasobId($z->getId());
            $uz->setZasobOpis($z->getNazwa());
            $uz->setPoziomDostepu($z->getPoziomDostepu());
            $uz->setModul($z->getModulFunkcja());
            $c1 = explode(',', $z->getPoziomDostepu());
            foreach ($c1 as $c) {
                $c = trim($c);
                $choicesPoziomDostepu[$c] = $c;
            }
            $c2 = explode(',', $z->getModulFunkcja());
            foreach ($c2 as $c) {
                $c = trim($c);
                $choicesModul[$c] = $c;
            }

            $uz->setZasobNazwa($z->getNazwa());
            //$uz->setSamaccountname($z->getId());
            $userzasoby[] = $uz;
        }

        //print_r($fromWhenPars['data']);
        $form = $this->createFormBuilder()
            ->add('action', HiddenType::class, array(
                'data' => $action
            ))
            ->add('wniosekId', HiddenType::class, array(
                'data' => $wniosekId
            ))
            ->add('samaccountnames', HiddenType::class, $samaccountnamesPars)
            ->add('fromWhen', HiddenType::class, $fromWhenPars)
            ->add('powod', HiddenType::class, $powodPars)
            ->add('userzasoby', CollectionType::class, array(
                'entry_type' => UserZasobyType::class,
                'entry_options' => array(
                    'is_sub_form' => true,
                    'data_uz' => $datauz,
                ),
                'allow_add'    => true,
                'allow_delete'    => true,
                'by_reference' => false,
                'label' => 'Zasoby',
                'prototype' => true,
                'constraints' => array(
                    new Constraints\Valid(),
                ),
                'data' => $userzasoby
            ))
            ->add('Dalej', SubmitType::class, array(
                'attr' => array(
                    'onclick' => 'beforeSubmit(event)',
                    'class' => 'btn btn-success col-sm-12',
                ),
            ))
            ->setAction($this->generateUrl('addResourcesToUsers'))
            ->setMethod('POST')
            ->getForm();

        if ($ndata == null) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                //die('temp blokuje by zbadac wnioski');
                $ndata = $form->getData();
                //print_r($ndata);
                //tworzy przypisania do zasobow
                $sams = array();
                $s1 = json_decode($ndata['samaccountnames']);
                foreach ($s1 as $k => $v) {
                    if ($v && $v != '' && $v != '_empty_') {
                        $sams[] = $k;
                    }
                }
                $msg = '';
                $msg2 = '';
                $powod = $ndata['powod'];
                $wniosekId = $ndata['wniosekId'];
                $wniosek = $this->getDoctrine()->getRepository(WniosekNadanieOdebranieZasobow::class)->find($wniosekId);
                //var_dump($ndata); die();
                foreach ($ndata['userzasoby'] as $oz) {
                    foreach ($sams as $currentsam) {
                        $zmianaupr = array();

                        //tu szukal podobnych dla tego zasobu ale teraz po polaczeniu z wnioskiami i nieaktywnymi to trzeba by warunek zwiekszyc
                        //$suz = $this->getDoctrine()->getManager()->getRepository(UserZasoby::class)->findOneBy(array('samaccountname' => $currentsam, 'zasobId' => $oz->getZasobId()));
                        $zasob = $this->getDoctrine()->getManager()->getRepository(Zasoby::class)->find($oz->getZasobId());
                        $admini = explode(',', $zasob->getAdministratorZasobu());
                        //var_dump($this->getUser()->getUsername(), $admini, $this->getUser()->getRoles());
                        if ($wniosekId == 0 && !in_array($this->getUser()->getUsername(), $admini, true) && !in_array(
                            'PARP_ADMIN',
                            $this->getUser()->getRoles(),
                            true
                        )) {
                            //jesli bez wniosku
                            //jesli nie admin_zasobu
                            //jesli nie parp_admin
                            //wtedy nie moze
                            throw new SecurityTestException('Tylko administrator zasobu (albo administrator AkD) może dodawać do swoich zasobów użytkowników bez wniosku!!!');
                        }

                        //if($suz == null){
                        if ($oz->getId() > 0) {
                            //$z2 = $this->getDoctrine()->getManager()->getRepository(UserZasoby::class)->find($oz->getId());
                            //$this->getDoctrine()->getManager()->remove($z2);
                            $z = $this->getDoctrine()->getManager()->getRepository(UserZasoby::class)->find($oz->getId());
                            $z->setModul($oz->getModul());
                            $z->setPoziomDostepu($oz->getPoziomDostepu());
                            $z->setSumowanieUprawnien($oz->getSumowanieUprawnien());
                            $z->setBezterminowo($oz->getBezterminowo());
                            $z->setAktywneOd(new \DateTime($oz->getAktywneOd()));
                            if ($oz->getAktywneDo() == '' || $oz->getBezterminowo()) {
                                $z->setAktywneDo(null);
                            } else {
                                $z->setAktywneDo($oz->getAktywneDo());
                            }
                            $z->setKanalDostepu($oz->getKanalDostepu());
                            $z->setUprawnieniaAdministracyjne($oz->getUprawnieniaAdministracyjne());
                            $z->setOdstepstwoOdProcedury($oz->getOdstepstwoOdProcedury());
                        } else {
                            $z = clone $oz;
                            $this->getDoctrine()->getManager()->persist($z);
                            $z->setAktywneOd(new \DateTime($z->getAktywneOd()));
                            if ($oz->getAktywneDo() == '' || $oz->getBezterminowo()) {
                                $z->setAktywneDo(null);
                            } else {
                                $z->setAktywneDo($oz->getAktywneDo());
                            }
                        }
                            $z->setCzyAktywne($wniosekId == 0);
                            $z->setCzyNadane(false);
                            $z->setWniosek($wniosek);

                            $z->setPowodNadania($powod);
                            $z->setSamaccountname($currentsam);

                            $msg = 'Dodaje usera '.$currentsam." do zasobu '".$this->get('rename_service')->zasobNazwa($oz->getZasobId())."'.";//." bo go nie ma !";
                        if ($wniosekId == 0) {
                            $this->addFlash('warning', $msg);
                        }
                            $zmianaupr[] = $zasob->getOpis();

                        if (count($zmianaupr) > 0 && $wniosekId == 0) {
                            $this->get('uprawnienia_service')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), $zmianaupr, array(), 'Zasoby', $oz->getZasobId(), $zasob->getAdministratorZasobu());
                        }
                    }
                }
                if ($wniosek) {
                    $wniosek->ustawPoleZasoby();
                }
                $this->getDoctrine()->getManager()->flush();

                if ($wniosek) {
                    $wniosek->ustawPoleZasoby();
                }
                $this->getDoctrine()->getManager()->flush();
                if ($wniosekId != 0) {
                    return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
                } else {
                    return $this->redirect($this->generateUrl('main'));
                }
            } else {
                $ndata = $form->getData();
                print_r($ndata);
                $ee = array();
                foreach ($form->getErrors() as $e) {
                    $ee[] = $e->getMessage();
                }

                print_r($ee);
                die('mam blad forma '.count($form->getErrors()).' '.$form->getErrorsAsString());
            }
        }

        $tmpl = $wniosek ? 'ParpMainBundle:NadawanieUprawnienZasobow:addUserResourcesByWniosek.html.twig' : 'ParpMainBundle:NadawanieUprawnienZasobow:addUserResources.html.twig';

        return $this->render($tmpl, array(
            'wniosek' => $wniosek,
            'wniosekId' => $wniosekId,
            'users' => $users,
            'form' => $form->createView(),
            'action' => $action
        ));
        //print_r($ndata); die();
    }
}
