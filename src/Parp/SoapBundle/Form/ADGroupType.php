<?php

namespace Parp\SoapBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ADGroupType extends AbstractType
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
            ->add('cn')
            ->add('member')
            ->add('distinguishedname')
            ->add('instancetype')
            ->add('whencreated')
            ->add('whenchanged')
            ->add('usncreated')
            ->add('usnchanged')
            ->add('name')
            ->add('objectguid')
            ->add('objectsid')
            ->add('samaccountname')
            ->add('samaccounttype')
            ->add('grouptype')
            ->add('objectcategory')
            ->add('dscorepropagationdata')
            ->add('dn')
            ->add('ADUsers')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\SoapBundle\Entity\ADGroup'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_soapbundle_adgroup';
    }
}
