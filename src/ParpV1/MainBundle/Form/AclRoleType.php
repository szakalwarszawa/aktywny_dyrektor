<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclRoleType extends AbstractType
{
    
    
    protected $ADUsers;
    protected $em;
    
    public function __construct($ADUsers, $em)
    {
        $this->ADUsers = $ADUsers;
        $this->em = $em;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\RoleTransformer($this->em, $builder->getData());
        $builder
            //->add('deletedAt')
            ->add('name')
            ->add('opis')
            ->add('actions')
            ->add($builder->create('users', 'choice', array(
                'choices' => $this->ADUsers,
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))
            
            ->addModelTransformer($transformer))
            //->add('users')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\AclRole'
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
