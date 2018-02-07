<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            'read_only' => false,
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
            'read_only' => false,
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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Section'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_section';
    }
}
