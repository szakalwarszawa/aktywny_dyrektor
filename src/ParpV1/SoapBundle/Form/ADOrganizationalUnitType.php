<?php

namespace ParpV1\SoapBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\SoapBundle\Entity\ADOrganizationalUnit;

class ADOrganizationalUnitType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deletedAt')
            ->add('objectclass')
            ->add('ou')
            ->add('distinguishedname')
            ->add('instancetype')
            ->add('whencreated')
            ->add('whenchanged')
            ->add('usncreated')
            ->add('usnchanged')
            ->add('name')
            ->add('objectguid')
            ->add('objectcategory')
            ->add('dscorepropagationdata')
            ->add('dn')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ADOrganizationalUnit::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_soapbundle_adorganizationalunit';
    }
}
