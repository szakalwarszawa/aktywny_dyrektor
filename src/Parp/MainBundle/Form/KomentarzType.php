<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KomentarzType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('samaccountname', 'text', array(
                'attr' => array('readonly' => true)
            ))
            ->add('createdAt', 'datetime', array(
                'attr' => array('readonly' => true),
                'widget' => 'single_text'
            ))
            ->add('tytul')
            ->add('opis','textarea')
            ->add('obiekt','hidden')
            ->add('obiektId','hidden')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Komentarz'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_komentarz';
    }
}
