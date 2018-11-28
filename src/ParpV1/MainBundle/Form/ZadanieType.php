<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZadanieType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\ParpDateTransformer();
        $builder
            //->add('deletedAt')
            ->add('nazwa', TextType::class, array('attr' => array('readonly' => true)))
            ->add('status', ChoiceType::class, array(
                'choices' => array('utworzone' => 'utworzone', 'zrealizowany' => 'zrealizowany', 'nie zrealizowany' => 'nie zrealizowany')
            ))
            ->add('opis', HiddenType::class, array('attr' => array('readonly' => true)))
            ->add('komentarz', TextareaType::class)
            ->add('osoby', TextType::class, array('attr' => array('readonly' => true)))
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
            
            ->add('ukonczonePrzez', TextType::class, array('attr' => array('readonly' => true)))
            ->add('obiekt', HiddenType::class)
            ->add('obiektId', HiddenType::class)
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Zadanie'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_zadanie';
    }
}
