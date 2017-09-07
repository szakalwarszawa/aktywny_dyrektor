<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PlikType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('nazwa')
            ->add('typ', 'choice', array('choices' => array('wniosek' => 'załącznik')))
            ->add('opis')
            ->add('file', 'file', array('data' => null, 'required' => false))
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
            'data_class' => 'ParpV1\MainBundle\Entity\Plik'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_plik';
    }
}
