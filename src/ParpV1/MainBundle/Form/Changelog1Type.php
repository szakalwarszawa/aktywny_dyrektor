<?php

namespace ParpV1\MainBundle\Form;

use ParpV1\MainBundle\Entity\Changelog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Changelog1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('createdAt')
            // ->add('deletedAt')
            ->add('samaccountname')
            ->add('wersja')
            ->add('dodatkowyTytul')
            ->add('opis')
            ->add('opublikowany')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Changelog::class,
        ]);
    }
}
