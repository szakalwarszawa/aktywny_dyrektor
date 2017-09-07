<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WniosekNadanieOdebranieZasobowEditorType extends AbstractType
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
            ->add('wniosek')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_wnioseknadanieodebraniezasoboweditor';
    }
}
