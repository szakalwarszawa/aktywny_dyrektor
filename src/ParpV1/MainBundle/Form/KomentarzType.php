<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KomentarzType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deletedAt', HiddenType::class)
            ->add('samaccountname', TextType::class, array(
                'attr' => array('readonly' => true),
                'label' => 'Osoba dodająca komentarz'
            ))
            ->add('tytul', TextType::class, ['label' => 'Tytuł komentarza'])
            ->add('opis', TextareaType::class, ['label' => 'Treść komentarza', 'attr' => ['maxlength' => 5000]])
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
            'data_class' => 'ParpV1\MainBundle\Entity\Komentarz'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_komentarz';
    }
}
