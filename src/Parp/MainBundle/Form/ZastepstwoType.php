<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZastepstwoType extends AbstractType
{
    protected $ADUsers;
    
    public function __construct($ADUsers){
        $this->ADUsers = $ADUsers;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('opis')
            
            ->add('ktoZastepuje', 'choice',  array(
                'choices' => $this->ADUsers,
                'required' => false, 'label' => 'Kto zastępuje', 'attr' => array('class' => 'select2'))
            )
            ->add('kogoZastepuje', 'choice',  array(
                'choices' => $this->ADUsers,
                'required' => false, 'label' => 'Kogo zastępuje', 'attr' => array('class' => 'select2'))
            )
            ->add('dataOd', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data od',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'Y-MM-d'
                    
                ))
            ->add('dataDo', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data do',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'Y-MM-d'
                    
                ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Zastepstwo'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_zastepstwo';
    }
}
