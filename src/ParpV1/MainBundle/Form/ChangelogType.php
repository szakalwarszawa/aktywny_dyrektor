<?php

namespace ParpV1\MainBundle\Form;

use ParpV1\MainBundle\Entity\Changelog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangelogType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('samaccountname', TextType::class, [
                'required' => false,
                'attr' => ['readonly' => true],
                'label' => 'Autor (nazwa użytkownika)',
                'empty_data' => 'System',
            ])
            ->add('dataWprowadzeniaZmiany', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'html5' => false,
                'attr' => [
                    'class' => 'datepicker',
                ],
            ])
            ->add('wersja')
            ->add('dodatkowyTytul')
            ->add('opis', null, [
                'required' => true,
            ])
            ->add('czyMarkdown', null, [
                'label' => 'Opis sformatowany w Markdown',
                ])
            ->add('opublikowany')
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Changelog::class,
        ]);
    }
}
