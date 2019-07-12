<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use ParpV1\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Formularz kolekcji AccessLevelGroupCollectionType
 */
class AccessLevelGroupCollectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $zasob = $options['zasob'];
        $builder
            ->add('accessLevelGroups', CollectionType::class, [
                'entry_type' => AccessLevelGroupType::class,
                'entry_options' => [
                    'zasob' => $zasob,
                    'label_attr' => ['class' => 'hidden']
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'constraints' => [new Valid()]

            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Utwórz'
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => Zasoby::class,
            ])
            ->setRequired('zasob')
            ->setAllowedTypes('zasob', Zasoby::class)
        ;
    }
}
