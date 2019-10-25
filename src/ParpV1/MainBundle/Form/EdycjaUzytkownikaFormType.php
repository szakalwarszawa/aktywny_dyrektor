<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\SoapBundle\Services\LdapService;
use ParpV1\MainBundle\Helper\AdUserHelper;
use ParpV1\MainBundle\Entity\Position;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Entity\Section;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use ParpV1\MainBundle\Constants\TakNieInterface;
use ParpV1\MainBundle\Entity\AclUserRole;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Elementy formularza bazują głównie na danych pobieranych bezpośrednio z AD.
 * Opiera się na kluczach tablicy zwracanej z AD które są zdefiniowane
 * w klasie AdUserConstants. W miejsca stałych lepiej nie wsadzać nic innego.
 */
class EdycjaUzytkownikaFormType extends AbstractType
{
    /**
     * @var LdapService
     */
    private $ldapService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY = 'Konto wyłączono z powodu rozwiązania stosunku pracy';

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_NIEOBECNOSC = 'Konto wyłączono z powodu nieobecności dłuższej niż 30 dni';

    /**
     * Typ formularza do edycji.
     * Umożliwia on edycję tylko wybranych pól.
     * Używany przy edycji użykownika przez PARP_BZK_1.
     * Skrócony formularz.
     *
     * @var int
     */
    const TYP_EDYCJA = 1;

    /**
     * Typ formularza nowego użytkownika.
     * Umożliwia edycję wszystkich pól.
     * Używany przy edycji użytkownika przez rolę PARP_ADMIN_REJESTRU_ZASOBOW.
     * Pełny formularz.
     *
     * @var int
     */

    const TYP_NOWY = 2;

    /**
     * Publiczny konstruktor
     *
     * @param LdapService $ldapService
     */
    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formType = $options['form_type'];
        $adUserHelper = null;
        if (self::TYP_EDYCJA === $formType) {
            $adUser = $this
                ->ldapService
                ->getUserFromAD($options['username'], null, null, 'wszyscyWszyscy')
            ;

            $adUserHelper = new AdUserHelper($adUser, $options['entity_manager']);
        }

        $this->entityManager = $options['entity_manager'];

        $builder
            ->add(AdUserConstants::LOGIN, TextType::class, [
                'required' => true,
                'label' => 'Nazwa konta',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $options['username'],
            ])
            ->add(AdUserConstants::IMIE_NAZWISKO, TextType::class, [
                'required' => true,
                'label' => 'Imię i nazwisko',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $adUserHelper ? $adUserHelper->getImieNazwisko() : null,
            ])
            ->add(AdUserConstants::STANOWISKO, EntityType::class, [
                'required' => true,
                'label' => 'Stanowisko',
                'class' => Position::class,
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'placeholder' => 'Proszę wybrać',
                'data' => $adUserHelper ? $adUserHelper->getStanowisko(true) : null,
            ])
            ->add(AdUserConstants::DODATKOWY_PODPIS, TextType::class, [
                'required' => false,
                'label' => 'Dodatkowy podpis w stopce',
                'data' => $adUserHelper ? $adUserHelper->getExtensionAttribute10() : null,
            ])
            ->add(AdUserConstants::DEPARTAMENT_NAZWA, EntityType::class, [
                'required' => true,
                'label' => 'Biuro / Departament',
                'class' => Departament::class,
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'placeholder' => 'Proszę wybrać',
                'data' => $adUserHelper ? $adUserHelper->getDepartamentNazwa(false, true) : null,
            ])
            ->add(AdUserConstants::PRZELOZONY, ChoiceType::class, [
                'required' => true,
                'label' => 'Przełożony',
                'choices' => $this
                    ->ldapService
                    ->getPrzelozeniJakoName(),
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'placeholder' => 'Proszę wybrać',
                'data' => $adUserHelper ? $adUserHelper->getPrzelozony() : null,
            ])
            ->add(AdUserConstants::SEKCJA_NAZWA, EntityType::class, [
                'required' => true,
                'label' => 'Sekcja',
                'class' => Section::class,
                // Ma być realizowane po stronie frontu
                // 'query_builder' => function (EntityRepository $entityRepository) use ($adUserHelper) {
                //     if (!$adUserHelper) {
                //         return $entityRepository
                //             ->createQueryBuilder('s')
                //         ;
                //     }

                //     return $entityRepository
                //         ->createQueryBuilder('s')
                //         ->join('s.departament', 'd')
                //         ->where('d.shortname = :short')
                //         ->setParameter('short', $adUserHelper? $adUserHelper::getDepartamentNazwa(true) : null)
                //     ;
                // },
                'group_by' => function ($choiceObject) {
                    $departament = $choiceObject->getDepartament();

                    if ($departament) {
                        $separatorText =  $departament->getShortname() . '  (' . $departament->getName() . ')';

                        return $separatorText;
                    }

                    return 'bez departamentu';
                },
                'placeholder' => 'Proszę wybrać',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $adUserHelper ? $adUserHelper->getSekcja(false, true) : null,
            ])
            ->add(AdUserConstants::WYGASA, DateType::class, [
                'required' => false,
                'label' => 'Data wygaśnięcia konta',
                'data' => $adUserHelper ? $adUserHelper->getKiedyWygasa() : null,
                'widget' => 'single_text',
                'html5' => false,
                'constraints' => [
                    new Assert\NotNull()
                ],
            ])
        ;
        if (self::TYP_NOWY !== $formType) {
            $builder
                ->add(AdUserConstants::WYLACZONE, ChoiceType::class, [
                    'required' => false,
                    'label' => 'Konto wyłączone w AD',
                    'choices' => [
                        'Nie' => TakNieInterface::NIE,
                        'Tak' => TakNieInterface::TAK,
                    ],
                    'constraints' => [
                        new Assert\NotNull()
                    ],
                    // 'placeholder' => 'Proszę wybrać',
                    'data' => $adUserHelper ? $adUserHelper->getCzyWylaczone() : null,
                ])
                ->add(AdUserConstants::POWOD_WYLACZENIA, ChoiceType::class, [
                    'required' => false,
                    'label' => 'Powód wyłączenia',
                    'choices' => [
                        'Konto wyłączono z powodu rozwiązania stosunku pracy' => AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY,
                        'Konto wyłączono z powodu nieobecności dłuższej niż 30 dni' => AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC
                    ],
                    'placeholder' => 'Proszę wybrać',
                ])
            ;
        }
            /*
            @feature
            ->add('ustawUprawnieniaPoczatkowe', CheckboxType::class, [
                'required' => false,
                'label' => 'Resetuj do uprawnień początkowych'
            ])*/
        $builder
            ->add('zmianaOd', DateType::class, [
                'label' => 'Zmiana obowiązuje od',
                'required' => true,
                'data' => new DateTime(),
                'widget' => 'single_text',
                'html5' => false,
            ])
        ;
        /*
        @feature
        if (self::TYP_NOWY !== $formType) {
            $builder
                ->add('roles', EntityType::class, [
                    'label' => 'Role w AkD',
                    'class' => AclRole::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => false,
                    'data' => $adUserHelper? $this->getUserRoles($options['username']) : null
                ])
            ;
        }
        */

        $builder
            ->add('shortForm', HiddenType::class, [
                'data' => $options['short_form'],
            ])
            ->add('formType', HiddenType::class, [
                'data' => $formType,
            ])
            ->add('zapisz', SubmitType::class)
        ;

        $builder->setMethod('POST');

        if ($adUserHelper) {
            $eventListener = function (FormEvent $formEvent) use ($adUserHelper) {
                $getDataErrors = $adUserHelper::getErrors();
                $form = $formEvent->getForm();
                foreach ($getDataErrors as $error) {
                    $formError = new FormError($error['message']);
                    $form
                        ->get($error['element'])
                        ->addError($formError)
                    ;

                    $formEvent
                        ->getForm()
                        ->addError($formError)
                    ;
                }
            };

            $builder
                ->addEventListener(FormEvents::PRE_SET_DATA, $eventListener)
            ;
        }

        if (self::TYP_NOWY !== $formType) {
            $builder
                ->get(AdUserConstants::POWOD_WYLACZENIA)
                ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent) {
                    $disableReason = $formEvent->getData();
                    $form = $formEvent->getForm();
                    $isDisabled = $form
                        ->getParent()
                        ->get(AdUserConstants::WYLACZONE)
                        ->getData()
                    ;

                    if (TakNieInterface::TAK === $isDisabled && empty($disableReason)) {
                        $formError = new FormError('Musisz podać powód wyłączenia konta.');
                        $form
                            ->addError($formError)
                        ;
                    }
                })
            ;
        }
    }

    /**
     * Zwraca kolekcję obiektów AclRole posiadane przez podanego użytkownika.
     *
     * @param string $username
     *
     * @return ArrayCollection
     */
    private function getUserRoles(string $username): ArrayCollection
    {
        $entityManager = $this->entityManager;
        $userRoles = $entityManager
            ->getRepository(AclUserRole::class)
            ->findBy([
                'samaccountname' => $username,
            ]);


        $aclRoleObjects = new ArrayCollection();
        foreach ($userRoles as $userRole) {
            $aclRoleObjects->add($userRole->getRole());
        }

        return $aclRoleObjects;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'short_form' => true,
            'username' => ''
        ));

        $resolver->setRequired([
            'entity_manager',
            'username',
            'form_type'
        ]);

        $resolver
            ->setAllowedTypes('short_form', 'bool')
            ->setAllowedTypes('username', 'string')
            ->setAllowedTypes('form_type', 'int')
            ->setAllowedTypes('entity_manager', EntityManager::class)
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_edycjauzytkownika';
    }
}
