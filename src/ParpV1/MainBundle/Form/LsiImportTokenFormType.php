<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\LsiImportToken;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Klasa LsiImportTokenFormType
 */
class LsiImportTokenFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('wniosek', HiddenType::class, array(
                'data' => $options['wniosek_nadanie_odebranie_zasobow']->getId(),
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Wygeneruj Token Importu LSI'
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => LsiImportToken::class,
            'wniosek_nadanie_odebranie_zasobow' => null,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parpv1_mainbundle_lsiimporttoken';
    }
}
