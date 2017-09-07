<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DaneRekordType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('symbolRekordId')
            ->add('imie')
            ->add('nazwisko')
            ->add('departament')
            ->add('stanowisko')
            ->add('umowa')
            ->add('umowaOd', 'date', ['widget' => 'single_text', 'required' => false])
            ->add('umowaDo', 'date', ['widget' => 'single_text', 'required' => false])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\DaneRekord'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_danerekord';
    }
}
