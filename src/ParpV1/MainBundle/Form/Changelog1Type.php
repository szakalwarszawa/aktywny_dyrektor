<?php

namespace ParpV1\MainBundle\Form;

use ParpV1\MainBundle\Entity\Changelog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Changelog1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('createdAt')
            // ->add('deletedAt')
            // ->add('samaccountname')
            ->add('samaccountname', TextType::class, [
                'required' => false,
                'attr' => ['readonly' => true],
                'label' => 'Autor (nazwa użytkownika)',
                'empty_data' => 'System',
                ])
            ->add('dataWprowadzeniaZmiany', DateType::class, ['widget' => 'single_text', 'required' => false])
            ->add('wersja')
            ->add('dodatkowyTytul')
            ->add('opis')
            ->add('opublikowany')
            // ->add('save', SubmitType::class, [
            //     'label' => 'Dodaj wpis',
            //     'attr'  => [
            //         'class' => 'btn btn-info'
            //     ]
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Changelog::class,
        ]);
    }
}
