<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Form;

use ParpV1\MainBundle\Entity\PositionGroups;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formularz PositionGroupsType
 */
class PositionGroupsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'required' => true,
                'label' => 'Nazwa grupy',
            ])
            ->add('description', null, [
                'required' => false,
                'label' => 'Dodatkowy opis',
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PositionGroups::class,
        ]);
    }
}
