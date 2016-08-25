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


            ->add('aktywneOd', 'text', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'placeholder' => 'Aktywne od'
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
                        'placeholder' => 'Aktywne do'
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
                    'WK' => 'WK - Wewnętrzny kablowy',
                    'DZ_O' => 'DZ_O - Zdalny, za pomocą komputera nie będącego własnością PARP',
                    'DZ_P' => 'DZ_P - Zdalny, za pomocą komputera będącego własnością PARP',
                    'WR' => 'WR - Wewnętrzny radiowy',
                    'WRK' => 'WRK - Wewnętrzny radiowy i kablowy'
                    
                ]
            ])
            ->add('uprawnieniaAdministracyjne')
            //->add('odstepstwoOdProcedury', 'text', ['attr' => ['placeholder' => 'Odstępstwo od procedury']])
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


    }
    protected function addChoicesFromDictionary($o, $form, $getter, $fieldName, $builder){
        //var_dump($this->datauz);
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
        $this->datauz[$fieldName] = isset($this->datauz[$fieldName]) ? $this->datauz[$fieldName] : "";

            $form->add($fieldName, /* NestedComboType::class */ 'choice',
                array("choices" => $choices,
                        'attr' => ['data-placeholder' => 'Wybierz'],
                        'data' => explode(";", $this->datauz[$fieldName]),//potrzebne by zaznaczal przy edycji
                        'multiple' => true,
                        'expanded' => false,
                        'attr' => ['class' => 'select2']
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
