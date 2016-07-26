<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AclRoleType extends AbstractType
{
    
    
    protected $ADUsers;
    protected $em;
    
    public function __construct($ADUsers, $em){
        $this->ADUsers = $ADUsers;
        $this->em = $em;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \Parp\MainBundle\Form\DataTransformer\RoleTransformer($this->em, $builder->getData());
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\AclRole'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_aclrole';
    }
}
