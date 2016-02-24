<?php
    
// src/AppBundle/Form/Type/GenderType.php
namespace Parp\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
class NestedComboType extends AbstractType
{
    var $_resolver;
    public function buildForm(\Symfony\Component\Form\FormBuilderInterface  $builder, array $options)
    {
/*
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            // ... adding the name field if needed
            $product = $event->getData();
            //$form = $event->getForm();
            if($product){
                //echo(".".$product."."); 
                $f = $event->getForm();   
                //print_r($f->get(''));
                
                $this->_resolver->setDefaults(array(
                    'choices' => array(
                        'm' => 'Male1',
                        'f' => 'Female1',
                    )
                ));
            }
            //die();
            //$form->add('name', 'text', array('mapped' => false));;//die('a');
        });
*/
        
        
        //print_r($options); die();
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        //print_r($resolver); die();
        $this->_resolver = $resolver;
        $this->_resolver->setDefaults(array(
            'choices' => array(
                'm' => 'Male',
                'f' => 'Female',
            )
        ));
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}