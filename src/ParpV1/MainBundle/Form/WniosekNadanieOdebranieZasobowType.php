<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Form\WniosekType;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;

class WniosekNadanieOdebranieZasobowType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            ->add('odebranie', HiddenType::class);

        $builder
            ->add('pracownikSpozaParp', CheckboxType::class, array(
                'required' => false,
                'label' => "Czy pracownik/pracownicy spoza PARP"
            ))
            ->add($builder->create('pracownicy', ChoiceType::class, array(
                'choices' => array_flip($options['ad_users']),
                'multiple' => true,
                'required' => false,
                'label' => 'Wybierz pracowników których dotyczy wniosek (pole obowiązkowe)',
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            ->add('pracownicySpozaParp', null, array(
                'required' => false,
                'label' => 'Pracownicy spoza PARP',
                'attr' => array(
                    'class' => 'tagAjaxInputNoAjax'
                )
            ))
            ->add('managerSpozaParp', ChoiceType::class, array(
                'choices' => array_flip($options['managerzy_spoza_parp']),
                'required' => false,
                'label' => 'Manager Pracowników spoza PARP',
                'attr' => array(
                    'class' => 'select2'
                    )
            ))
            ->add('wniosek', WniosekType::class, array(
                'label' => false
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => WniosekNadanieOdebranieZasobow::class,
            'ad_users' => array(),
            'managerzy_spoza_parp' => array(),
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_wnioseknadanieodebraniezasobow';
    }
}
