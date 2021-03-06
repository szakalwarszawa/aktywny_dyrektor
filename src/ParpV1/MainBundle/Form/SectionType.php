<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\Section;

class SectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array(
            'required' => false,
            'label' => 'Pełna nazwa',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));

        $builder->add('shortname', null, array(
            'required' => false,
            'label' => 'Skrót',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));

        $builder->add('departament', null, ['attr' => ['class' => 'select2']]);
        $builder->add('kierownikName', null, ['attr' => ['readonly' => true]]);
        $builder->add('kierownikDN', null, ['attr' => ['readonly' => true]]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Section::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_section';
    }
}
