<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use DateTime;

/**
 * Formularz przeniesiony do osobnej klasy z Main/DefaultController
 * Potrzebne są metadane z tego formularza do sprawdzania zmian.
 *
 * @todo trzeba przenieść z formularza html`owe atrybuty do twiga
 * @todo ogarnąć te readonly -> przekazać do szablonu przy renderowaniu
 * @todo poprawić szablon twigowy
 * @todo ogarnąć opcje formularza - jest ich za dużo i to jakieś głupoty są...
 * @todo refaktoryzacja
 */
class EdycjaUzytkownikaFormType extends AbstractType
{
    /**
     * @todo na tych stringach operuje X innych rzeczy..
     *
     * @var string
     */
    const WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY = 'Konto wyłączono z powodu rozwiązania stosunku pracy';

    /**
     * @todo jak wyżej
     *
     * @var string
     */
    const WYLACZENIE_KONTA_NIEOBECNOSC = 'Konto wyłączono z powodu nieobecności dłuższej niż 21 dni';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $admin = $options['opcje']['admin'];
        $kadry1 = $options['opcje']['kadry1'];
        $kadry2 = $options['opcje']['kadry2'];
        $pracownikTymczasowy = $options['opcje']['pracownik_tymczasowy'];
        $titles = $options['opcje']['titles'];
        $sections = $options['opcje']['sections'];
        $info = $options['opcje']['info'];
        $departments = $options['opcje']['departments'];
        $przelozeni = $options['opcje']['przelozeni'];
        $now = new DateTime();
        $rights = $options['opcje']['rights'];
        $nowy = $options['opcje']['nowy'];
        $initialRights = $options['opcje']['initial_rights'];
        $roles = $options['opcje']['roles'];

        $builder
            ->add('samaccountname', TextType::class, array(
                'required'   => false,
                'label'      => 'Nazwa konta',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => true
                ),
            ))
            ->add('cn', TextType::class, array(
                'required'   => false,
                'label'      => 'Nazwisko i Imię', //'Imię i Nazwisko',//'Nazwisko i Imię',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => (!$admin && !$kadry2 && !$pracownikTymczasowy),
                ),
            ))
            ->add('initials', TextType::class, array(
                'required'   => false,
                'label'      => 'Inicjały',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => (!$admin && !$kadry2 && !$pracownikTymczasowy),
                ),
            ))
            ->add('title', ChoiceType::class, array(
                //                'class' => 'ParpMainBundle:Position',
                'required'   => false,
                'label'      => 'Stanowisko',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'disabled' => (!$admin && !$kadry2 && !$pracownikTymczasowy),
                    'onchange' => 'zaznaczUstawieniePoczatkowych()',
                    'data-toggle' => 'select2',
                ),
                //'data' => @$defaultData["title"],
                'choices'    => $titles,
                //                'mapped'=>false,
            ))
            ->add('infoNew', HiddenType::class, array(
                'mapped'     => false,
                'label'      => false,
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => (!$admin),
                ),

            ))
            ->add('info', ChoiceType::class, array(
                'required'   => false,
                'label'      => 'Sekcja',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'disabled' => (!$admin && !$kadry1 && !$kadry2 && !$pracownikTymczasowy),
                    'onchange' => 'zaznaczUstawieniePoczatkowych()',
                    'data-toggle' => 'select2',
                ),
                'choices'    => $sections,
                'data' => $info,
            ))
            ->add('department', ChoiceType::class, array(
                'required'   => false,
                'label'      => 'Biuro / Departament',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'disabled' => (!$admin && !$kadry2 && !$pracownikTymczasowy),
                    'onchange' => 'zaznaczUstawieniePoczatkowych()',
                    'data-toggle' => 'select2',
                ),
                'choices'    => $departments,
                //'data' => @$defaultData["department"],
            ))
            ->add('manager', ChoiceType::class, array(
                'required'   => false,
                'label'      => 'Przełożony',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => (!$admin && !$kadry1 && !$kadry2),
                    'data-toggle' => 'select2',

                    //'disabled' => (!$admin && !$kadry1 && !$kadry2)

                ),
                'choices'    => $przelozeni
                //'data' => @$defaultData['manager']
            ))
            ->add('accountExpires', TextType::class, array(
                'attr'       => array(
                    'class' => 'form-control',
                ),
                //'widget' => 'single_text',
                'label'      => 'Data wygaśnięcia konta',
                //'format' => 'dd-MM-yyyy',
                //                'input' => 'datetime',
                'label_attr' => array(
                    'class'    => 'col-sm-4 control-label',
                    'readonly' => (!$admin && !$kadry1 && !$kadry2),
                ),
                'required'   => false,
                //'data' => @$expires
            ))
            ->add('fromWhen', TextType::class, array(
                'attr'       => array(
                    'class' => 'form-control',
                ),
                //                'widget' => 'single_text',
                'label'      => 'Zmiana obowiązuje od',
                //                'format' => 'dd-MM-yyyy',
                //                'input' => 'datetime',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'required'   => false,
                'data'       => $now->format('Y-m-d'),
            ))
            ->add('initialrights', ChoiceType::class, array(
                'required'   => false,
                'label'      => 'Uprawnienia początkowe',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'disabled' => (!$admin),
                    'data-toggle' => 'select2',
                ),
                'choices'    => $rights,
                'data'       => ($nowy ? ['UPP'] : $initialRights),

                //'data' => (@$defaultData["initialrights"]),
                'multiple'   => true,
                'expanded'   => false,
            ))
            ->add('roles', ChoiceType::class, array(
                'required'   => false,
                'label'      => 'Role w AkD',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'readonly' => (!$admin),
                    'disabled' => (!$admin),
                    'data-toggle' => 'select2',
                ),
                'choices'    => $roles,
                //'data' => (@$defaultData["initialrights"]),
                'multiple'   => true,
                'expanded'   => false,
            ))
            ->add('isDisabled', ChoiceType::class, array(
                'required'   => true,
                'label'      => 'Konto wyłączone w AD',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'attr'       => array(
                    'class'    => 'form-control',
                    'disabled' => (!$admin && !$kadry1 && !$kadry2),
                    'data-toggle' => 'select2',
                ),
                'choices'    => array(
                    'NIE' => 0,
                    'TAK' => 1,
                ),
                //'data' => @$defaultData["department"],
            ))
            ->add('disableDescription', ChoiceType::class, array(
                'label'    => 'Podaj powód wyłączenia konta',
                'choices'  => array(
                    'Konto wyłączono z powodu nieobecności dłuższej niż 21 dni' => self::WYLACZENIE_KONTA_NIEOBECNOSC,
                    'Konto wyłączono z powodu rozwiązania stosunku pracy'       => self::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY,
                ),
                'required' => false,
                'placeholder' => 'Proszę wybrać',
                'attr'     => array(
                    'disabled' => (!$admin && !$kadry1 && !$kadry2),
                ),
            ))
            ->add('ustawUprawnieniaPoczatkowe', CheckboxType::class, array(
                'label'      => 'Resetuj do uprawnień początkowych',
                'label_attr' => array(
                    'class' => 'col-sm-4 control-label',
                ),
                'required'   => false,
                'attr'       => array(
                    'class'    => 'form-control2',
                    'required' => false,
                ),
                'data'       => false,
            ))
            ->setMethod('POST')
        ;

        if (!(!$admin && !$kadry1 && !$kadry2)) {
            $builder->add('zapisz', SubmitType::class, array(
                'attr' => array(
                    'class'    => 'btn btn-success col-sm-12',
                    'disabled' => (!$admin && !$kadry1 && !$kadry2),
                ),
            ));
        }
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

        $resolver->setAllowedTypes('opcje', 'array');
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_edycjauzytkownika';
    }
}
