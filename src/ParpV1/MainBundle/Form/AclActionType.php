<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\AclAction;

class AclActionType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('name')
            ->add('skrot')
            ->add('opis')
            ->add('roles', null, array('by_reference' => false))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AclAction::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_aclaction';
    }
}
