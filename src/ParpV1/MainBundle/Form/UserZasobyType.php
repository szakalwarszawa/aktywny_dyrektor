<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use ParpV1\MainBundle\Entity\UserZasoby;

class UserZasobyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = new \Datetime();
        $d1 = $options['data_uz'] ? $options['data_uz']['aktywneOd'] : $now->format("d-m-Y");
        //print_r($options['data_uz']);
        $builder
            ->add('id', HiddenType::class)
            ->add('samaccountname', HiddenType::class)
            ->add('zasobNazwa', HiddenType::class)
            ->add('zasobId', HiddenType::class)


            ->add('aktywneOd', TextType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'placeholder' => 'Aktywne od'
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne od',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $d1,
                    //'format' => 'Y-m-d'
                ))
            ->add('bezterminowo', CheckboxType::class, ['required' => false, 'attr' => ['class' => 'inputBezterminowo']])
            ->add('sumowanieUprawnien', CheckboxType::class, ['required' => false])
            //->add('aktywneOdPomijac')
            ->add('aktywneDo', TextType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker inputAktywneDo',
                        'placeholder' => 'Aktywne do'
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne do',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => (isset($options['data_uz']['aktywneDo']) ? $options['data_uz']['aktywneDo']->format("Y-m-d") : null), //$now->format("Y-m-d")),
                    //'format' => 'Y-m-d'
                ))
            ->add('kanalDostepu', ChoiceType::class, [
                'choices' => array_flip(array(
                    'WK' => 'WK - Wewnętrzny kablowy',
                    'DZ_O' => 'DZ_O - Zdalny, za pomocą komputera nie będącego własnością PARP',
                    'DZ_P' => 'DZ_P - Zdalny, za pomocą komputera będącego własnością PARP',
                    'WR' => 'WR - Wewnętrzny radiowy',
                    'WRK' => 'WRK - Wewnętrzny radiowy i kablowy'
                ))
            ])
            ->add('uprawnieniaAdministracyjne')
        ;



        if ($options['is_sub_form']) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($builder) {
                    $form = $event->getForm();
                    $o = $event->getData();
                    $choices = array();
                    if ($o) {
                        $this->addChoicesFromDictionary($o, $form, "getPoziomDostepu", "poziomDostepu", $builder);
                        $this->addChoicesFromDictionary($o, $form, "getModul", "modul", $builder);
                    }
                }
            );
        }
    }
    protected function addChoicesFromDictionary($o, $form, $getter, $fieldName, $builder)
    {
        //var_dump($options['data_uz']);
        $ch = explode(";", $o->{$getter}());
        $choices = array("do wypełnienia przez właściciela zasobu" => "do wypełnienia przez właściciela zasobu");
        foreach ($ch as $c) {
            $c = trim($c);
            if ($c != "") {
                $choices[$c] = $c;
            }
        }
        //print_r($choices);
        if (count($choices) == 1) {
            $choices = array('nie dotyczy' => 'nie dotyczy');
        }
        $options['data_uz'][$fieldName] = isset($options['data_uz'][$fieldName]) ? $options['data_uz'][$fieldName] : "";

            $form->add(
                $fieldName, /* NestedComboType::class */
                ChoiceType::class,
                array('choices' => $choices,
                        'data' => explode(";", $options['data_uz'][$fieldName]),//potrzebne by zaznaczal przy edycji
                        'multiple' => true,
                        'expanded' => false,
                        'required' => true,
                        'attr' => ['class' => 'select2 multiwybor '.$fieldName, 'required' => false]
                    )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UserZasoby::class,
            'is_sub_form' => true,
            'data_uz' => null,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_userzasoby';
    }
}
