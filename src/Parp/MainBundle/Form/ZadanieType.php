<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZadanieType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \Parp\MainBundle\Form\DataTransformer\ParpDateTransformer();
        $builder
            //->add('deletedAt')
            ->add('nazwa', 'text', array('attr' => array('readonly' => true)))
            ->add('status', 'choice', array(
                'choices' => array('utworzone' => 'utworzone', 'zrealizowany' => 'zrealizowany', 'nie zrealizowany' => 'nie zrealizowany')
            ))
            ->add('opis', 'hidden', array('attr' => array('readonly' => true)))
            ->add('komentarz', 'textarea')
            ->add('osoby', 'text', array('attr' => array('readonly' => true)))
            //datetime
            ->add(
                $builder->create('dataDodania', 'text', array(
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                        'readonly' => true
                    ),
                    'label' => 'Data dodania',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                ))
                ->addModelTransformer($transformer)
            )
            ->add(
                $builder->create('dataUkonczenia', 'text', array(
                    //'block_name' => 'custom_name',
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                    ),
                    'label' => 'Data ukończenia',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                ))
                ->addModelTransformer($transformer)
            )
            
            ->add('ukonczonePrzez', 'text', array('attr' => array('readonly' => true)))
            ->add('obiekt', 'hidden')
            ->add('obiektId', 'hidden')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Zadanie'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_zadanie';
    }
}
