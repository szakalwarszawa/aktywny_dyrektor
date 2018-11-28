<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            ->add('odebranie', HiddenType::class);
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
            
            
        $builder->add('pracownikSpozaParp', CheckboxType::class, array('required' => false, 'label' => "Czy pracownik/pracownicy spoza PARP"))

            ->add($builder->create('pracownicy', 'choice', array(
                'choices' => $this->ADUsers,
                'multiple' => true,
                'required' => false,
                'label' => 'Wybierz pracowników których dotyczy wniosek (pole obowiązkowe)',
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            
            ->add('pracownicySpozaParp', null, array('required' => false, 'label' => 'Pracownicy spoza PARP', 'attr' => array('class' => 'tagAjaxInputNoAjax')))
            
            ->add('managerSpozaParp', ChoiceType::class, array(
                'choices' => $this->managerzySpozaPARP,
                'required' => false, 'label' => 'Manager Pracowników spoza PARP', 'attr' => array('class' => 'select2')))
            ->add('wniosek', new \ParpV1\MainBundle\Form\WniosekType($this->ADUsers), array(
                'data_class' => 'ParpV1\MainBundle\Entity\Wniosek',
                'label' => false));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow'
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
