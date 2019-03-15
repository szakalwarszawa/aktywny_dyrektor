<?php declare(strict_types=1);

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
use ParpV1\MainBundle\Entity\AclRole;
use Doctrine\ORM\EntityRepository;
use ParpV1\MainBundle\Entity\AclUserRole;
use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\MainBundle\Services\UserService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * Formularz przeniesiony do osobnej klasy z Main/DefaultController
 * Potrzebne są metadane z tego formularza do sprawdzania zmian.
 *
 * @todo trzeba przenieść z formularza html`owe atrybuty do twiga
 * @todo ogarnąć te readonly -> przekazać do szablonu przy renderowaniu
 * @todo poprawić szablon twigowy
 * @todo ogarnąć opcje formularza - jest ich za dużo i to jakieś głupoty są...
 * @todo refaktoryzacja
 *
 * ----
 *
 * Elementy formularza bazują głównie na danych pobieranych bezpośrednio z AD.
 * Opiera się na kluczach tablicy zwracanej z AD które są zdefiniowane
 * w klasie AdUserConstants. W miejsca stałych lepiej nie wsadzać nic innego.
 *
 * Prawa dostępu do edycji lub wyświetlenia są pobierane
 * z pliku konfiguracyjnego userEditFormPrivileges.yml
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
     * @var UserService
     */
    private $userService;

    public $xd;

    /**
     * Typ formularza do edycji.
     *
     * @var int
     */
    const TYP_EDYCJA = 1;

    /**
     * Typ formularza nowego użytkownika.
     *
     * @var int
     */
    const TYP_NOWY = 2;

    /**
     * Publiczny konstruktor
     *
     * @param LdapService $ldapService
     */
    public function __construct(LdapService $ldapService, UserService $userService)
    {
        $this->ldapService = $ldapService;
        $this->currentUser = $userService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $adUser = $this
            ->ldapService
            ->getUserFromAD($options['username'])
        ;
        $adUserHelper = new AdUserHelper($adUser, $options['entity_manager']);
        $this->entityManager = $options['entity_manager'];

        $builder
            ->add(AdUserConstants::LOGIN, TextType::class, [
                'required' => false,
                'label' => 'Nazwa konta',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $options['username'],
            ])
            ->add(AdUserConstants::IMIE_NAZWISKO, TextType::class, [
                'required' => false,
                'label' => 'Imię i nazwisko',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $adUserHelper->getImieNazwisko(),
            ])
            ->add(AdUserConstants::STANOWISKO, EntityType::class, [
                'required' => false,
                'label' => 'Stanowisko',
                'class' => Position::class,
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
            ],
                'data' => $adUserHelper->getStanowisko(true),
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
                'data' => $adUserHelper->getDepartamentNazwa(false, true),
            ])
            ->add(AdUserConstants::PRZELOZONY, ChoiceType::class, [
                'required' => false,
                'label' => 'Przełożony',
                'choices' => $this
                    ->ldapService
                    ->getPrzelozeniJakoName(),
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $adUserHelper->getPrzelozony(),
            ])
            ->add(AdUserConstants::SEKCJA_NAZWA, EntityType::class, [
                'required' => false,
                'label' => 'Sekcja',
                'class' => Section::class,
                'query_builder' => function (EntityRepository $entityRepository) use ($adUserHelper) {
                    return $entityRepository
                        ->createQueryBuilder('s')
                        ->join('s.departament', 'd')
                        ->where('d.shortname = :short')
                        ->setParameter('short', $adUserHelper::getDepartamentNazwa(true))
                    ;
                },
                'group_by' => function ($choiceObject) {
                    $departament = $choiceObject->getDepartament()?
                        $choiceObject
                            ->getDepartament()
                            ->getShortname() : 'bez departamentu'
                    ;

                    return $departament;
                },
                'placeholder' => 'Proszę wybrać',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'data' => $adUserHelper->getSekcja(false, true),
            ])
            ->add(AdUserConstants::WYGASA, DateType::class, [
                'required' => false,
                'label' => 'Data wygaśnięcia konta',
                'data' => $adUserHelper->getKiedyWygasa(),
            ])
            ->add(AdUserConstants::WYLACZONE, ChoiceType::class, [
                'required' => false,
                'label' => 'Konto wyłączone w AD',
                'choices' => [
                    'Tak' => TakNieInterface::TAK,
                    'Nie' => TakNieInterface::NIE,
                ],
                'constraints' => [
                    new Assert\NotNull()
                ],
                'placeholder' => 'Proszę wybrać',
                'data' => $adUserHelper->getCzyWylaczone(),
            ])
            ->add(AdUserConstants::POWOD_WYLACZENIA, ChoiceType::class, [
                'required' => false,
                'label' => 'Powód wyłączenia',
                'choices' => [
                    'Konto wyłączono z powodu rozwiązania stosunku pracy' => AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY,
                    'Konto wyłączono z powodu nieobecności dłuższej niż 21 dni' => AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC
                ],
                'placeholder' => 'Proszę wybrać',
            ])
            /*
            @feature
            ->add('ustawUprawnieniaPoczatkowe', CheckboxType::class, [
                'required' => false,
                'label' => 'Resetuj do uprawnień początkowych'
            ])*/
            ->add('zmianaOd', DateTimeType::class, [
                'label' => 'Zmiana obowiązuje od',
                'required' => false,
                'data' => new DateTime()
            ])
            ->add('roles', EntityType::class, [
                'label' => 'Role w AkD',
                'class' => AclRole::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'data' => $this->getRoleUzytkownika($options['username'])
            ])
            ->add('zapisz', SubmitType::class)
        ;

        $builder->setMethod('POST');

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
            });
    }

    /**
     * Zwraca kolekcję obiektów AclRole posiadane przez podanego użytkownika.
     *
     * @param string $username
     *
     * @return ArrayCollection
     */
    private function getRoleUzytkownika(string $username): ArrayCollection
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
            'opcje' => []
        ));

        $resolver->setRequired([
            'entity_manager',
            'username',
            'typ_formularza'
        ]);

        $resolver
            ->setAllowedTypes('opcje', 'array')
            ->setAllowedTypes('username', 'string')
            ->setAllowedTypes('typ_formularza', 'int')
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
