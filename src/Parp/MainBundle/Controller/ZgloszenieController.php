<?php

/**
 * Kontroler obsługujący wysyłanie i odbieranie zgłoszeń do/z Redmine.
 *
 * @author Robert Muchacki robert_muchacki@parp.gov.pl
 *
 * @version GIT: $Id$ In development. Very unstable
 */

namespace Parp\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Klasa kontrolera obsługująca wysyłanie i odbieranie zgłoszeń do/z Redmine.
 *
 * Class ZgloszenieController
 */
class ZgloszenieController extends Controller
{
    /**
     * Akcja wyświetlająca formularz do tworzenia nowego zgłoszenia.
     *
     * @Route("/pomoc_techniczna/nowa/{uri}", name="nowa_pomoc_techniczna")
     *
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function noweZgloszenieAction($uri = null)
    {
        $dane_wstepne = array("uri" => urldecode($uri));
        $form = $this->createFormBuilder($dane_wstepne)
            ->setAction($this->generateUrl('nowa_pomoc_techniczna_submit'))
            ->add('temat', 'text', array(
                'label' => 'Temat zgłoszenia',
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
            ->add('imie_nazwisko', 'text', array(
                'label' => 'Imię i Nazwisko',
                'data' => $this->getUser()->getImie().' '.$this->getUser()->getNazwisko(),
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
        ->add('email', 'email', array(
                 'label' => 'Proszę podać email kontaktowy',
                 'data' => $this->getUser()->getEmail(),
                 'attr' => array(
                    'class' => 'form-control col-xs-5',
                 ),
                 'label_attr' => array(
                    'class' => 'col-xs-2',
                 ),
                 'constraints' => array(
                     new NotBlank(array('message' => 'Pole email kontaktowy jest wymagane.')),
                     new Email(array('message' => 'Podany email kontaktowy jest niepoprawny.'))
                 ),
                ))
            ->add('telefon', 'text', array(
                    'label' => 'Proszę podać telefon kontaktowy',
                    'attr' => array(
                        'class' => 'form-control col-xs-5',
                    ),
                    'label_attr' => array(
                        'class' => 'col-xs-2',
                    ),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Pole telefon kontaktowy jest wymagane.')),
                    ),
                ))
          ->add('opis', 'textarea', array(
                'label' => 'Proszę opisać występujący problem techniczny',
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
            ->add('uri', 'hidden')
            ->add('zapisz', 'submit', array(
                'label' => 'Wyślij zgłoszenie',
                'attr' => array(
                    'class' => 'btn btn-success pull-right',
                ),
            ))
            ->getForm();

        return array(
                'formularz' => $form->createView(),
            );
    }

    /**
     * Akcja obsługująca wysłanie formularza z noweZgloszenieAction.
     *
     * @see noweZgloszenieAction()
     *
     * @Route("/pomoc_techniczna/nowa", name="nowa_pomoc_techniczna_submit")
     *
     * @Method("POST")
     *
     * @Template()
     *
     * @return array
     *
     * @param Request $request
     *
     * @TODO Zrobić czyszczenie wprowadzanych danych aby zapobiec ewentualnemu SQL-Injection
     */
    public function noweZgloszenieSubmitAction(Request $request)
    {
        $formularz = $request->get('form');
        $userId = $this->getUser()->getId();
        $temat = $formularz['temat'];
        $imie_nazwisko = $formularz['imie_nazwisko'];
        $email = $formularz['email'];
        $telefon = $formularz['telefon'];
        $opis = $formularz['opis'];
        $kategoria = 11; # Zgłoszone przez użytkownika
        $uri = strip_tags($formularz['uri']);
        
//		putZgloszenieBeneficjenta($id_beneficjenta, $temat, $opis, $kategoria, $uri = null, $czy_prywatna = true,$komunikat_systemowy = null,$zgloszenie_id = null,$podmiot = null,$email = null, $telefon = null, $imie_nazwisko = null);
        $odpowiedz_tmp = $this->get('parp.redmine')->putZgloszenieBeneficjenta($userId, $temat, $opis, $kategoria, $uri, true, null, null, null, $email, $telefon, $imie_nazwisko);

        if (preg_match('~\{(?:[^{}]|(?R))*\}~', $odpowiedz_tmp, $odpowiedz_json)) {
            $odpowiedz = json_decode($odpowiedz_json[0], true);
                
            $this->get('parp.redmine')->dodajNotatke($odpowiedz['issue']['id']);

            $request->getSession()->getFlashBag()->add(
                'notice',
                'Zarejestrowano zgłoszenie techniczne. Posiada ono numer '.$odpowiedz['issue']['id'].'.'.
                'W każdej chwili można sprawdzić stan zgłoszenia na tej stronie.'
            );
        }
        return $this->redirectToRoute('pomoc_techniczna');
    }

    /**
     * Akcja pobierająca wszystkie zgłoszenia danego beneficjenta.
     *
     * @Route("/pomoc_techniczna", name="pomoc_techniczna")
     *
     * @Template()
     *
     * @return array
     *
     * @todo Na razie ID Beneficjenta jest wprowadzone na sztywno do momentu rozpracowania mechanizmu logowania
     */
    public function zgloszeniaAction()
    {

        $userId = $this->getUser()->getId();
        $odpowiedz = $this->get('parp.redmine')->getZgloszeniaBeneficjenta($userId);

        $zgloszenia = json_decode($odpowiedz, true);

        return array(
            'zgloszenia' => $zgloszenia['issues'],
        );
    }
}
