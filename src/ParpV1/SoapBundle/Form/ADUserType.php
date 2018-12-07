<?php

namespace ParpV1\SoapBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\SoapBundle\Entity\ADUser;

class ADUserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deletedAt')
            ->add('samaccountname')
            ->add('isDisabled')
            ->add('accountExpires')
            ->add('name')
            ->add('email')
            ->add('initials')
            ->add('title')
            ->add('info')
            ->add('department')
            ->add('division')
            ->add('lastlogon')
            ->add('manager')
            ->add('thumbnailphoto')
            ->add('useraccountcontrol')
            ->add('distinguishedname')
            ->add('cn')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ADUser::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_soapbundle_aduser';
    }
}
