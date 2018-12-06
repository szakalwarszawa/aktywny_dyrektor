<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartamentType extends AbstractType
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

        $builder->add('nameInRekord', null, array(
            'required' => false,
            'label' => 'Pełna nazwa w Systemie Rekord',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));

        $builder->add('grupyAD', null, array(
            'required' => false,
            'label' => 'Nazwy grup w AD do ktoóych ma wpisać każdego użytkownika tego departamentu',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control tagAjaxInput',
            ),
        ));

        $builder->add('skroconaNazwaRekord', null, array(
            'required' => true,
            'label' => 'Skrócona nazwa w rekord',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));

        $builder->add('dyrektor', null, array(
            'required' => true,
            'label' => 'Dyrektor',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));

        $builder->add('dyrektorDN', null, array(
            'required' => true,
            'label' => 'Dyrektor DN',
            'label_attr' => array(
                'class' => 'col-sm-2 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            ),
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Departament'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_departament';
    }
}
