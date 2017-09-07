<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WniosekNadanieOdebranieZasobowType extends AbstractType
{
    protected $ADUsers;
    protected $entity;
    protected $managerzySpozaPARP;
    
    public function __construct($ADUsers, $managerzySpozaPARP, $entity)
    {
        $this->ADUsers = $ADUsers;
        $this->entity = $entity;
        $this->managerzySpozaPARP = $managerzySpozaPARP;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \Parp\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            ->add('odebranie', 'hidden');
        //die(". ".$this->entity->getOdebranie());
        if ($this->entity->getOdebranie()) {
/*
            $builder->add('dataOdebrania', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data odebrania uprawnień',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ));
*/
        }
            
            
        $builder->add('pracownikSpozaParp', 'checkbox', array('required' => false, 'label' => "Czy pracownik/pracownicy spoza PARP"))

            ->add($builder->create('pracownicy', 'choice', array(
                'choices' => $this->ADUsers,
                'multiple' => true,
                'required' => false,
                'label' => 'Wybierz pracowników których dotyczy wniosek (pole obowiązkowe)',
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            
            ->add('pracownicySpozaParp', null, array('required' => false, 'label' => 'Pracownicy spoza PARP', 'attr' => array('class' => 'tagAjaxInputNoAjax')))
            
            ->add('managerSpozaParp', 'choice', array(
                'choices' => $this->managerzySpozaPARP,
                'required' => false, 'label' => 'Manager Pracowników spoza PARP', 'attr' => array('class' => 'select2')))
            ->add('wniosek', new \Parp\MainBundle\Form\WniosekType($this->ADUsers), array(
                'data_class' => 'Parp\MainBundle\Entity\Wniosek',
                'label' => false));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_wnioseknadanieodebraniezasobow';
    }
}
