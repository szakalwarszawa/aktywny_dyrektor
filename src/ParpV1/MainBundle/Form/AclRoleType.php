<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\MainBundle\Form\DataTransformer\RoleTransformer;

class AclRoleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new RoleTransformer($options['entity_manager'], $builder->getData());
        $builder
            ->add('name')
            ->add('opis')
            ->add('actions')
            ->add($builder->create('users', ChoiceType::class, array(
                'choices' => array_flip($options['ad_users']),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))
            ->addModelTransformer($transformer))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AclRole::class,
            'ad_users' => array(),
            'entity_manager' => null,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_aclrole';
    }
}
