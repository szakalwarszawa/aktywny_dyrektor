<?php

namespace ParpV1\MainBundle\Form;

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
            ->add('deletedAt', 'hidden')
            ->add('samaccountname', 'text', array(
                'attr' => array('readonly' => true),
                'label' => 'Osoba dodająca komentarz'
            ))
            ->add('createdAt', 'datetime', array(
                'attr' => array('readonly' => true),
                'widget' => 'single_text',
                'label' => 'Kiedy utworzono komentarz'
            ))
            ->add('tytul', 'text', ['label' => 'Tytuł komentarza'])
            ->add('opis', 'textarea', ['label' => 'Treść komentarza', 'attr' => ['maxlength' => 5000]])
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
            'data_class' => 'ParpV1\MainBundle\Entity\Komentarz'
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
