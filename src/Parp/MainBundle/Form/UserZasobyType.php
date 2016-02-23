<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Parp\MainBundle\Form\Type\NestedComboType;

class UserZasobyType extends AbstractType
{
    private $choicesModul;
    private $choicesPoziomDostepu;

    public function __construct($choicesModul, $choicesPoziomDostepu)
    {
        $this->choicesModul = $choicesModul;
        $this->choicesPoziomDostepu = $choicesPoziomDostepu;
    }
    
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = new \Datetime();
        $builder
            ->add('samaccountname', 'hidden')
            ->add('zasobNazwa', 'hidden')
            ->add('zasobId', 'hidden')
            //->add('loginDoZasobu')
            ->add('modul', /* NestedComboType::class */ 'choice', array("required" => false, 'empty_value' => null,"choices" => $this->choicesModul))
            ->add('poziomDostepu', /* NestedComboType::class */ 'choice', array("required" => false, 'empty_value' => null, "choices" => $this->choicesPoziomDostepu))
            ->add('aktywneOd', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne od',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $now->format("d-m-Y")
                ))
            ->add('bezterminowo')
            //->add('aktywneOdPomijac')
            ->add('aktywneDo', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne do',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $now->format("d-m-Y")
                ))
            ->add('kanalDostepu')
            ->add('uprawnieniaAdministracyjne')
            ->add('odstepstwoOdProcedury')
        ;
        $builder->addEventListener(
        FormEvents::PRE_SET_DATA,
        function(FormEvent $event) use($builder){
            $form = $event->getForm();
            $o = $event->getData();
            $choices = array();
            if($o){
                $this->addChoicesFromDictionary($o, $form, "getPoziomDostepu", "poziomDostepu");
                $this->addChoicesFromDictionary($o, $form, "getModul", "modul");
                
            }
            

        });
    }
    protected function addChoicesFromDictionary($o, $form, $getter, $fieldName){
        $ch = explode(",", $o->{$getter}());            
        $choices = array();
        foreach($ch as $c){
            $c = trim($c);
            if($c != "")
                $choices[$c] = $c;
        }
        //print_r($choices);
        if(count($choices) == 0){
            $choices = array('n/a' => 'n/a');
        }
        $form->add($fieldName, NestedComboType::class,
            array("choices" => $choices)
        );
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\UserZasoby'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_userzasoby';
    }
}
