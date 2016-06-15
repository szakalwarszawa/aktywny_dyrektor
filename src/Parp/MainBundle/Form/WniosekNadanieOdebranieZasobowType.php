<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WniosekNadanieOdebranieZasobowType extends AbstractType
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
        $transformer = new \Parp\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            //->add('deletedAt')
            ->add('numer', 'text', array(
                'attr' => array('readonly' => true)
            ))
            ->add('jednostkaOrganizacyjna', 'text', array(
                'attr' => array('readonly' => true)
            ))
            ->add('status', 'entity', array(
                'class' => 'ParpMainBundle:WniosekNadanieOdebranieZasobowStatus',
                'attr' => array('readonly' => true, 'disabled' => 'disabled'),
            ))
            ->add('pracownikSpozaParp')
            ->add($builder->create('pracownicy', 'choice', array(
                'choices' => $this->ADUsers,
                'multiple' => true,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            
            ->add('pracownicySpozaParp', 'text', array(
                'mapped' => true,
                'required' => false
            ))
            ->add('createdBy', 'text', array(
                'attr' => array('readonly' => true),
                'label' => 'Utworzony przez'
            ))
            ->add('createdAt', 'datetime', array(
                'attr' => array('readonly' => true),
                'label' => 'Utworzony dnia',
                'widget' => 'single_text'
            ))
            ->add('lockedBy', 'text', array(
                'attr' => array('readonly' => true),
                'label' => 'Edytowany (zablokowany) przez'
            ))
            ->add('lockedAt', 'datetime', array(
                'attr' => array('readonly' => true),
                'label' => 'Edytowany (zablokowany) dnia',
                'widget' => 'single_text'
            ))
        ;
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
