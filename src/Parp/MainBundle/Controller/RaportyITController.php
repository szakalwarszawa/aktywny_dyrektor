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
 * RaportyIT controller.
 *
 * @Route("/RaportyIT")
 */
class RaportyITController extends Controller
{
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
            $ldap = $this->get('ldap_service');
            $ndata = $form->getData();
            $daneRekord = $this->getData($ndata['rok'], $ndata['miesiac']);
            
            $daneZRekorda = [];
            foreach($daneRekord as $dr){
                $daneZRekorda[$dr['login']] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac']);
            }
            
            $zmianyDepartamentow = [];
            $repo = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:DaneRekord');
            $historyRepo = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\HistoriaWersji');
            $zmianyDep = $repo->findChangesDeprtamentInMonth($ndata['rok'], $ndata['miesiac']);
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
                        $dep1 = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\Departament')->findOneByNameInRekord($wpis->getDepartament());
                        $dep2 = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\Departament')->findOneByNameInRekord($wpisNowy->getDepartament());
                        $akcja = "Zmiana departamentu z '".$dep1->getName()."' na '".$dep2->getName()."'";
                        //var_dump($zmiana); 
                        $dr = [
                            'login' => $wpisNowy->getLogin(),
                            'nazwisko' => $wpisNowy->getNazwisko(),
                            'imie' => $wpisNowy->getImie(),
                            'umowaOd' => $wpisNowy->getUmowaOd(),
                            'umowaDo' => $wpisNowy->getUmowaDo(),
                            'dataZmiany' => $zmiana['loggedAt']->format("Y-m-d"),
                        ];
                        
                        $daneZRekorda[$wpis->getLogin()] = $this->zrobRekordZRekorda($dr, $ndata['rok'], $ndata['miesiac'], $akcja);
                    }
                }
            }
            //var_dump($zmianyDep); die();
            /*
            $users = $ldap->getAllFromAD();
            $daneAD = [];
            foreach($users as $u){
                $daneAD[$dr['login']] = $this->zrobRekord($dr, $ndata['rok'], $ndata['miesiac']);
            }
            */
            
            return $this->render('ParpMainBundle:RaportyIT:wynik.html.twig', ['daneZRekorda' => $daneZRekorda]);   
            //return $this->generateExcel($data, $rok);
        }
        
        return [
            'form' => $form->createView()    
        ];
    }
    protected function zrobRekordZRekorda($dr, $rok, $miesiac, $akcja = ''){
        $miesiac = str_pad($miesiac, 2, '0', STR_PAD_LEFT);
        //die($rok."-".$miesiac);
        $dataZmiany = "";
        $ldap = $this->get('ldap_service');
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
            'umowaOd' => $dr['umowaOd'],
            'umowaDo' => $dr['umowaDo'],
            'expiry' => $user[0]['accountexpires'],
            'akcja' => $akcja,
            'data' => (isset($dr['dataZmiany']) ? $dr['dataZmiany'] : $dataZmiany),
        ];
    }
    protected function getData($rok, $miesiac){
        return $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:DaneRekord')->findChangesInMonth($rok, $miesiac);
    }
}