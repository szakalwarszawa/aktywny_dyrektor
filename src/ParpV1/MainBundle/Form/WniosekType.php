<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class WniosekType extends AbstractType
{
    protected $ADUsers;

    public function __construct($ADUsers)
    {
        $this->ADUsers = $ADUsers;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder

            ->add('numer', TextType::class, array(
                'attr' => array('readonly' => true)
            ))
            ->add('jednostkaOrganizacyjna', TextType::class, array(
                'attr' => array('readonly' => true)
            ))
            ->add('status', EntityType::class, array(
                'class' => 'ParpMainBundle:WniosekStatus',
                'attr' => array('readonly' => true, 'disabled' => 'disabled'),
            ))

            ->add('createdBy', TextType::class, array(
                'attr' => array('readonly' => true),
                'label' => 'Utworzony przez'
            ))
            ->add('createdAt', DateTimeType::class, array(
                'attr' => array('readonly' => true),
                'label' => 'Utworzony dnia',
                'widget' => 'single_text',
                'format' => 'Y-MM-d H:mm:s'
            ))
            ->add('lockedBy', TextType::class, array(
                'attr' => array('readonly' => true),
                'label' => 'Edytowany (zablokowany) przez'
            ))
            ->add('lockedAt', DateTimeType::class, array(
                'attr' => array('readonly' => true),
                'label' => 'Edytowany (zablokowany) dnia',
                'widget' => 'single_text',
                'format' => 'Y-MM-d H:mm:s'
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Wniosek'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_wniosek';
    }
}
