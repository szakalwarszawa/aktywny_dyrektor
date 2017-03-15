<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZastepstwoType extends AbstractType
{
    protected $ADUser;
    protected $ADUsers;
    
    public function __construct($ADUser, $ADUsers){
        $this->ADUser = $ADUser;
        $this->ADUsers = $ADUsers;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('opis', 'textarea', ['required' => true]);
        $builder->add('ktoZastepuje', 'choice',  array(
                'choices' => $this->ADUsers,
                'required' => false, 'label' => 'Kto zastępuje', 'attr' => array('class' => 'select2'))
            );    
        if(in_array("PARP_ADMIN", $this->ADUser->getRoles()) || in_array("PARP_ADMIN_ZASTEPSTW", $this->ADUser->getRoles())){
            //PARP_ADMIN oraz PARP_ADMIN_ZASTEPSTW moga ustawic kogolowiek jako kogo zastepuja
            $builder->add('kogoZastepuje', 'choice',  array(
                'choices' => $this->ADUsers,
                'required' => false, 'label' => 'Kogo zastępuje', 'attr' => array('class' => 'select2'))
            );
        }
        elseif(in_array("PARP_DB_ZASTEPSTWA", $this->ADUser->getRoles())){
            //PARP_DB_ZASTEPSTWA moga ustawic kogolowiek z DB jako kogo zastepuja
            $builder->add('kogoZastepuje', 'choice',  array(
                    'choices' => $this->ADUsers,
                    'required' => false, 'label' => 'Kogo zastępuje', 'attr' => array('class' => 'select2'))
            );
        }
        else{
            //reszta normalnych osob, ma ustawionego tylko siebie jako kogoZastepuje
            $builder->add('kogoZastepuje', 'text',  array(
                'required' => false, 'label' => 'Kogo zastępuje', 'data' => $this->ADUser->getUsername(), 'attr' => array('readonly' => true))
            );
        } 
            
            
        $builder->add('dataOd', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                    ),
                    'label' => 'Data od',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm'
                    
                ))
            ->add('dataDo', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                    ),
                    'label' => 'Data do',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm'
                    
                ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Zastepstwo'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_zastepstwo';
    }
}
