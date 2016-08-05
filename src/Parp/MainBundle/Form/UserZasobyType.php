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
    private $isSubForm;
    private $datauz;

    public function __construct($choicesModul, $choicesPoziomDostepu, $isSubForm = true, $datauz = null)
    {
        $this->choicesModul = $choicesModul;
        $this->choicesPoziomDostepu = $choicesPoziomDostepu;
        $this->isSubForm = $isSubForm;
        $this->datauz = $datauz;
    }
    
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = new \Datetime();
        $d1 = $this->datauz ? $this->datauz['aktywneOd'] : $now->format("d-m-Y");
        //print_r($this->datauz);
        $builder
            ->add('idd', 'hidden')
            ->add('samaccountname', 'hidden')
            ->add('zasobNazwa', 'hidden')
            ->add('zasobId', 'hidden')
            //->add('loginDoZasobu')
            ->add('modul', /* NestedComboType::class */ 'choice', array(
                "required" => false, 
                'empty_value' => null,
                "choices" => $this->choicesModul,
                    //'data' => ($this->datauz ? $this->datauz['modul'] : "")
                )
            )
            ->add('poziomDostepu', /* NestedComboType::class */ 'choice', array(
                "required" => false, 
                'empty_value' => null, 
                "choices" => $this->choicesPoziomDostepu,
                    //'data' => ($this->datauz ? $this->datauz['poziomDostepu'] : "")
                )
            )
            ->add('aktywneOd', 'text', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne od',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $d1,
                    //'format' => 'Y-m-d'
                ))
            ->add('bezterminowo', 'checkbox', ['required' => false, 'attr' => ['class' => 'inputBezterminowo']])
            ->add('sumowanieUprawnien', 'checkbox', ['required' => false])
            //->add('aktywneOdPomijac')
            ->add('aktywneDo', 'text', array(
                    'attr' => array(
                        'class' => 'form-control datepicker inputAktywneDo',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Aktywne do',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => (isset($this->datauz['aktywneDo']) ? $this->datauz['aktywneDo']->format("Y-m-d") : null), //$now->format("Y-m-d")),
                    //'format' => 'Y-m-d'
                ))
            ->add('kanalDostepu', 'choice', [
                'choices' => [
                    'DZ_O' => 'DZ_O - Zdalny, za pomocą komputera nie będącego własnością PARP',
                    'DZ_P' => 'DZ_P - Zdalny, za pomocą komputera będącego własnością PARP',
                    'WK' => 'WK - Wewnętrzny kablowy',
                    'WR' => 'WR - Wewnętrzny radiowy',
                    'WRK' => 'WRK - Wewnętrzny radiowy i kablowy'
                    
                ]
            ])
            ->add('uprawnieniaAdministracyjne')
            ->add('odstepstwoOdProcedury')
        ;
        if($this->isSubForm){
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
    }
    protected function addChoicesFromDictionary($o, $form, $getter, $fieldName){
        $ch = explode(";", $o->{$getter}());            
        $choices = array("do wypełnienia przez administratora zasobu" => "do wypełnienia przez administratora zasobu");
        foreach($ch as $c){
            $c = trim($c);
            if($c != "")
                $choices[$c] = $c;
        }
        //print_r($choices);
        if(count($choices) == 1){
            $choices = array('nie dotyczy' => 'nie dotyczy');
        }
        $form->add($fieldName, NestedComboType::class,
            array("choices" => $choices,
                    'data' => (isset($this->datauz[$fieldName]) ? $this->datauz[$fieldName] : "")
                )
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
