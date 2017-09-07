<?php

namespace ParpV1\SoapBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\SoapBundle\Entity\ADUser'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_soapbundle_aduser';
    }
}
