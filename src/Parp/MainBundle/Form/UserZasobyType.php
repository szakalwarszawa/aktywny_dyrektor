<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Parp\MainBundle\Form\Type\NestedComboType;
use Symfony\Component\Form\CallbackTransformer;


class UserZasobyType extends AbstractType
{
    private $choicesModul;
    private $choicesPoziomDostepu;
    private $isSubForm;
    private $datauz;
    private $transformer;

    public function __construct($choicesModul, $choicesPoziomDostepu, $isSubForm = true, $datauz = null)
    {
        
        $this->transformer = new \Parp\MainBundle\Form\DataTransformer\StringToArrayTransformer();
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
/*
     ;
   $builder->add($builder->create('modul', 'choice', [
                "required" => false, 
                "empty_value" => null,
                "choices" => [$this->choicesModul],
                "multiple" => true,
                'expanded' => true
                ]
            )->addModelTransformer($this->transformer));

        $builder
*/

            ->add('modul', 'choice', array(
                "required" => false, 
                'empty_value' => null,
                "choices" => $this->choicesModul,
                'multiple' => true,
                'expanded' => true
                    //'data' => ($this->datauz ? $this->datauz['modul'] : "")
                )
            )


/*
        $builder->add($builder->create('poziomDostepu',  'choice', array(
                "required" => false, 
                'empty_value' => null, 
                "choices" => $this->choicesPoziomDostepu,
                'multiple' => true,
                'expanded' => true
                    //'data' => ($this->datauz ? $this->datauz['poziomDostepu'] : "")
                )
            )->addModelTransformer($this->transformer));
*/

            ->add('poziomDostepu',  'choice', array(
                "required" => false, 
                'empty_value' => null, 
                "choices" => $this->choicesPoziomDostepu,
                //'multiple' => true,
                //'expanded' => true
                    //'data' => ($this->datauz ? $this->datauz['poziomDostepu'] : "")
                )
            )

/*         $builder */ ->add('aktywneOd', 'text', array(
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
                    $this->addChoicesFromDictionary($o, $form, "getPoziomDostepu", "poziomDostepu", $builder);
                    $this->addChoicesFromDictionary($o, $form, "getModul", "modul", $builder);
                    
                }
                
    
            });
        }

        $builder->get('modul')
            ->addModelTransformer(new \Symfony\Component\Form\CallbackTransformer(
                function ($tagsAsArray) {
                    var_dump($tagsAsArray);
                    echo "!!!CallbackTransformer1!!!";
                    //var_dump($tagsAsArray);
                    //return [$tagsAsArray];
                    //var_dump($tagsAsArray);
                    // transform the array to a string
                    if($tagsAsArray)
                        return explode(', ', $tagsAsArray);
                    else
                        return ""; //[];
                },
                function ($tagsAsString) {
                    echo "!!!CallbackTransformer2!!!";
                    //return $tagsAsString;
                    //var_dump($tagsAsString);
                    // transform the string back to an array
                    if($tagsAsString)
                        return explode(', ', $tagsAsString);
                    else
                        return "";
                }
            ))
        ;

    }
    protected function addChoicesFromDictionary($o, $form, $getter, $fieldName, $builder){
        $ch = explode(";", $o->{$getter}());            
        $choices = array("do wypełnienia przez właściciela zasobu" => "do wypełnienia przez właściciela zasobu");
        foreach($ch as $c){
            $c = trim($c);
            if($c != "")
                $choices[$c] = $c;
        }
        //print_r($choices);
        if(count($choices) == 1){
            $choices = array('nie dotyczy' => 'nie dotyczy');
        }
        if(!isset($this->datauz[$fieldName])){
            //$this->datauz[$fieldName] = current($choices);
            //echo ".".$fieldName." ".(isset($this->datauz[$fieldName]) ? $this->datauz[$fieldName] : "").".";
        }else{
            //echo("Jest ustawiony ".$this->datauz[$fieldName]);
        }
        $this->datauz[$fieldName] = isset($this->datauz[$fieldName]) ? $this->datauz[$fieldName] : "";

        if($fieldName == "modul2"){
/*
            $builder->add($builder->create('modul', 'choice', [
                        "required" => false, 
                        "empty_value" => null,
                        "choices" => $choices, //[$this->choicesModul],
                        'data' => null,
                        "multiple" => true,
                        'expanded' => true
                        ]
                    )->addModelTransformer($this->transformer));
*/
        }else{
            //print_r($this->datauz);
            //echo "!!!";
            $form->add($fieldName, /* NestedComboType::class */ 'choice',
                array("choices" => $choices,
                        'data' => $fieldName == "modul" ? null : $this->datauz[$fieldName],
                        'multiple' => $fieldName == "modul",
                        'expanded' => $fieldName == "modul"
                    )
            );
        }
        
/*
        $builder->add($builder->create($fieldName, NestedComboType::class,
            array("choices" => $choices,
                    //'data' => [$this->datauz[$fieldName]],
                    'multiple' => true,
                    'expanded' => true
                )
            )->addModelTransformer($this->transformer));
*/
        
        
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
