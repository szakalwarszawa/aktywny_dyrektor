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
            ->add('pracownikSpozaParp')

            ->add($builder->create('pracownicy', 'choice', array(
                'choices' => $this->ADUsers,
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            
            ->add('pracownicySpozaParp', null, array('required' => false, 'label' => 'Pracownicy spoza PARP', 'attr' => array('class' => 'tagAjaxInputNoAjax')))
            
            ->add('wniosek', new \Parp\MainBundle\Form\WniosekType($this->ADUsers), array(
                'data_class' => 'Parp\MainBundle\Entity\Wniosek')
            )
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
