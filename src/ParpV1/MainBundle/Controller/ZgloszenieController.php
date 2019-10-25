<?php

/**
 * Kontroler obsługujący wysyłanie i odbieranie zgłoszeń do/z Redmine.
 *
 * @author Robert Muchacki robert_muchacki@parp.gov.pl
 *
 * @version GIT: $Id$ In development. Very unstable
 */

namespace ParpV1\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

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
        $user = $this->getUser();
        $ad = $this->get('ldap_service')->getUserFromAD($user->getUsername());
        //echo "<pre>"; print_r($ad); die();
        $dane_wstepne = array("uri" => urldecode($uri));
        $form = $this->createFormBuilder($dane_wstepne)
            ->setAction($this->generateUrl('nowa_pomoc_techniczna_submit'))
            ->add('temat', TextareaType::class, array(
                'label' => 'Temat zgłoszenia',
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
            ->add('imie_nazwisko', TextareaType::class, array(
                'label' => 'Imię i Nazwisko',
                'data' => trim(@$ad[0]['name']),
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
            ->add('email', EmailType::class, array(
                 'label' => 'Proszę podać email kontaktowy',
                 'data' => trim(@$ad[0]['samaccountname']) . "@parp.gov.pl",//trim(@$ad[0]['email']),
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
            ->add('telefon', TextareaType::class, array(
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
          ->add('opis', TextareaType::class, array(
                'label' => 'Proszę opisać występujący problem techniczny',
                'attr' => array(
                    'class' => 'form-control col-xs-5',
                ),
                'label_attr' => array(
                    'class' => 'col-xs-2',
                ),
            ))
            ->add('uri', HiddenType::class)
            ->add('zapisz', SubmitType::class, array(
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
        $userId = $this->getUser()->getUsername();
        $temat = $formularz['temat'];
        $imie_nazwisko = $formularz['imie_nazwisko'];
        $email = $formularz['email'];
        $telefon = $formularz['telefon'];
        $opis = $formularz['opis'];
        $kategoria = 22; # Zgłoszone przez użytkownika
        $uri = strip_tags($formularz['uri']);

        // putZgloszenieBeneficjenta($id_beneficjenta, $temat, $opis, $kategoria, $uri = null, $czy_prywatna = true,$komunikat_systemowy = null,$zgloszenie_id = null,$podmiot = null,$email = null, $telefon = null, $imie_nazwisko = null);
        $odpowiedz_tmp = $this->get('parp.redmine')->putZgloszenieBeneficjenta($userId, $temat, $opis, $kategoria, $uri, true, null, null, null, $email, $telefon, $imie_nazwisko);

        if (preg_match('~\{(?:[^{}]|(?R))*\}~', $odpowiedz_tmp, $odpowiedz_json)) {
            $odpowiedz = json_decode($odpowiedz_json[0], true);

            $this->get('parp.redmine')->dodajNotatke($odpowiedz['issue']['id']);

            $request->getSession()->getFlashBag()->add(
                'notice',
                'Zarejestrowano zgłoszenie techniczne. Posiada ono numer ' . $odpowiedz['issue']['id'] . '.' .
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
//var_dump($this->getUser()); die();

        $ad = $this->get('ldap_service')->getUserFromAD($this->getUser()->getUsername());

        $userId = $ad[0]['samaccountname']; //$this->getUser()->getName();
        $odpowiedz = $this->get('parp.redmine')->getZgloszeniaBeneficjenta($userId);

        $zgloszenia = json_decode($odpowiedz, true);

        //echo "<pre>"; print_r($zgloszenia['issues']); die();

        return array(
            'zgloszenia' => $zgloszenia['issues'],
        );
    }

    /**
     * @Route("/zgloszenie/nowe", name="nowe_zgloszenie")
     *
     * @Template()
     * @return array
     */
    public function dodajZgloszenieAction(Request $request)
    {

        $form = $this->createFormBuilder()->setAction($this->generateUrl('nowe_zgloszenie'))
                ->add('podmiot', TextareaType::class, array('label' => 'Nazwa podmiotu', 'attr' => array(
                        'class' => 'form-control col-xs-5',
                    ),
                    'label_attr' => array(
                        'class' => 'col-xs-2',
                    ),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Pole nazwa podmiotu jest wymagane.')),
                    ),
                ))
                ->add('temat', TextareaType::class, array(
                    'label' => 'Temat zgłoszenia',
                    'attr' => array(
                        'class' => 'form-control col-xs-5',
                    ),
                    'label_attr' => array(
                        'class' => 'col-xs-2',
                    ),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Pole temat zgłoszenia jest wymagane.')),
                    ),
                ))
                ->add('opis', TextareaType::class, array(
                    'label' => 'Proszę opisać występujący problem techniczny',
                    'attr' => array(
                        'class' => 'form-control col-xs-5',
                    ),
                    'label_attr' => array(
                        'class' => 'col-xs-2',
                    ),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Pole opis problemu technicznego jest wymagane.')),
                    ),
                ))
                ->add('email', EmailType::class, array(
                    'label' => 'Proszę podać email kontaktowy',
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
                 ->add('telefon', TextareaType::class, array(
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
               ->add('uri', HiddenType::class)
               ->add('zapisz', SubmitType::class, array(
                    'label' => 'Wyślij zgłoszenie',
                    'attr' => array(
                        'class' => 'btn btn-success pull-right',
                    ),
                ))
                ->add('sciezka', HiddenType::class, array(
                    'empty_data' => ''))
                ->getForm();

        // jezeli adres powrotny raz ustwiony to go nie zmieniamy
        $sciezka = $form->get('sciezka')->getData();
        if (empty($sciezka)) {
            $form->get('sciezka')->setData($request->headers->get('referer'));
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $this->wyslijZgloszenie($data);

            /* 2270 - niby zawsze powinien być referer jeżeli mamy błąd - ale jak widać w zgłoszniu nie koniecznie.
             * Wywalało "Cannot redirect to an empty URL."
             * Dlatego kierujemy na domową gdy empty
             */
            $path = $form->get('sciezka')->getData();
            if (empty($path)) {
                $path = $this->generateUrl('home');
            }

            return $this->redirect($path);
        }

        return array(
            'formularz' => $form->createView(),
        );
    }

    private function wyslijZgloszenie($formularz)
    {
        $podmiot = $formularz['podmiot'];
        $temat = $formularz['temat'];
        $opis = $formularz['opis'];
        $kategoria = 22; # Zgłoszone przez użytkownika
        $email = $formularz['email'];
        $telefon = $formularz['telefon'];
        $uri = strip_tags($formularz['uri']);

        // niezalogowany więc 0
        $userId = 0;
        // putZgloszenieBeneficjenta($id_beneficjenta, $temat, $opis, $kategoria, $uri = null, $czy_prywatna = true,$komunikat_systemowy = null,$zgloszenie_id = null,$podmiot = null,$email = null, $telefon = null, $imie_nazwisko = null);
        $odpowiedz_tmp = $this->get('parp.redmine')->putZgloszenieBeneficjenta($userId, $temat, $opis, $kategoria, $uri, true, null, null, $podmiot, $email, $telefon);

        if (preg_match('~\{(?:[^{}]|(?R))*\}~', $odpowiedz_tmp, $odpowiedz_json)) {
            $odpowiedz = json_decode($odpowiedz_json[0], true);

            $this->addFlash(
                'success',
                'Pomyślnie zgłoszono problem techniczny. Numer Twojego zgłoszenia to ' . $odpowiedz['issue']['id'] . '. Prosimy powoływać się na niego w kontaktach z PARP.'
            );
        }
    }
}
