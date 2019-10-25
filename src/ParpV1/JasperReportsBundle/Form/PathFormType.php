<?php

declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Form;

use ParpV1\JasperReportsBundle\Entity\Path;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Formularz PathFormType
 */
class PathFormType extends AbstractType
{
    /**
     * @see AbstractType
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'Adres raportu lub folderu',
                'required' => true,
            ])
            ->add('isRepository', CheckboxType::class, [
                'required' => false,
                'label' => 'Folder zawierający raporty',
            ])
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'Tytuł raportu'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Wyślij'
            ])
        ;
    }

    /**
     * @see AbstractType
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Path::class,
        ]);
    }
}
