<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use ParpV1\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer;
use ParpV1\MainBundle\Entity\AccessLevelGroup;

/**
 * Formularz wystepujący głównie w kolekcji dlatego atrybuty przekazywane są tutaj.
 *
 * AccessLevelGroupType
 */
class AccessLevelGroupType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $zasob = $options['zasob'];
        $builder
            ->add('groupName', TextType::class, [
                'label' => 'Nazwa grupy',
                'attr' => [
                    'placeholder' => 'Wprowadź opis grupy'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Wprowadź opis grupy'
                ]

            ])
            ->add('accessLevels', ChoiceType::class, [
                'label' => 'Poziomy dostępu',
                'multiple' => true,
                'choices' => array_flip($zasob->getPoziomyDostepuAsArray(true)),
                'attr' => [
                    'class' => 'select2',
                ]
            ])
        ;
        $builder
            ->get('accessLevels')
            ->addModelTransformer(new StringToArrayTransformer(';'))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => AccessLevelGroup::class,
            ])
            ->setRequired('zasob')
            ->setAllowedTypes('zasob', Zasoby::class)
        ;
    }
}
