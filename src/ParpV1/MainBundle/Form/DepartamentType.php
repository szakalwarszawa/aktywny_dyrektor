<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DepartamentType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('name', null, array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Pełna nazwa',
                    'label_attr' => array(
                        'class' => 'col-sm-2 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                )))
                ->add('shortname', null, array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Skrót',
                    'label_attr' => array(
                        'class' => 'col-sm-2 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                )))
                ->add('nameInRekord', null, array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Pełna nazwa w Systemie Rekord',
                    'label_attr' => array(
                        'class' => 'col-sm-2 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                )))
                ->add('grupyAD', null, array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Nazwy grup w AD do ktoóych ma wpisać każdego użytkownika tego departamentu',
                    'label_attr' => array(
                        'class' => 'col-sm-2 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control tagAjaxInput',
                )))
            ->add('skroconaNazwaRekord', null, array(
                'required' => true,
                'read_only' => false,
                'label' => 'Skrócona nazwa w rekord',
                'label_attr' => array(
                    'class' => 'col-sm-2 control-label',
                ),
                'attr' => array(
                    'class' => 'form-control',
            )))
            ->add('dyrektor', null, array(
                'required' => true,
                'read_only' => false,
                'label' => 'Dyrektor',
                'label_attr' => array(
                    'class' => 'col-sm-2 control-label',
                ),
                'attr' => array(
                    'class' => 'form-control',
            )))
            ->add('dyrektorDN', null, array(
                'required' => true,
                'read_only' => false,
                'label' => 'Dyrektor DN',
                'label_attr' => array(
                    'class' => 'col-sm-2 control-label',
                ),
                'attr' => array(
                    'class' => 'form-control',
            )))
/*
            ->add('save', 'button', array('label' => 'Dodaj Biuro/Departament',
            'attr' => array('class' => 'btn btn-primary'),

        ))*/;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Departament'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_departament';
    }
}
