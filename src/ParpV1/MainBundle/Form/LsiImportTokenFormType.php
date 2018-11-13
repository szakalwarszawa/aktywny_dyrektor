<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use ParpV1\MainBundle\Entity\LsiImportToken;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

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
            ->add('submit', SubmitType::class, array())
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => LsiImportToken::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'wniosek_nadanie_odebranie_zasobow' => null,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parpv1_mainbundle_lsiimporttoken';
    }
}
