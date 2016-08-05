<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZasobyType extends AbstractType
{
    protected $nazwaLabel;
    
    protected $container;
    
    public function __construct($container, $nazwaLabel = "Nazwa"){
        $this->container = $container;
        $this->nazwaLabel = $nazwaLabel;
    }
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $adminiMulti = false; //in_array("PARP_ADMIN2", $this->container->getUser()->getRoles());
        
        $ldap = $this->container->get('ldap_service');
        
        $admini = $ldap->getAdministratorzyZasobow();
        
        $transformer = new \Parp\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            ->add('nazwa', 'text', ['label' => $this->nazwaLabel])
            ->add('opis', 'hidden')//jest drugie pole opis z importu ecm
            ->add('biuro', 'hidden');
        if($adminiMulti){
            $builder->add($builder->create('wlascicielZasobu', 'choice', array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer));
        }else{
            $builder->add('wlascicielZasobu', 'choice', array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => false,
                'required' => false,
                'attr' => array('class' => 'select2')
            ));
        }
            
        $builder->add($builder->create('powiernicyWlascicielaZasobu', 'choice', array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorZasobu', 'choice', array(
                'choices' => $admini,
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorTechnicznyZasobu', 'choice', array(
                'choices' => $ldap->getAdministratorzyTechniczniZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2')
            ))->addModelTransformer($transformer))
            
            
            ->add('uzytkownicy', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzni" => "Zewnętrzni")
            ))
            ->add('daneOsobowe')
            ->add('komorkaOrgazniacyjna', 'entity', array(
                'class' => 'Parp\MainBundle\Entity\Departament',
                'choice_value' => function($dep){
                    return $dep ? (is_object($dep) ? $dep->getName() : $dep) : "___BRAK___";
                },
                'label' => 'Komórka organizacyjna'
            ))
            ->add('miejsceInstalacji')
            ->add('opisZasobu')
            ->add('modulFunkcja', 'text', ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('poziomDostepu', 'text', ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('grupyAD', 'text', array(
                'attr' => array('class' => 'tagAjaxInput'), 'required' => false
            ))
            
            
            ->add('dataZakonczeniaWdrozenia', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data zakończenia wdrożenia',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ))
            ->add('wykonawca', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzny" => "Zewnętrzny")
            ))
            ->add('nazwaWykonawcy')
            ->add('asystaTechniczna')
            ->add('dataWygasnieciaAsystyTechnicznej', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data wygaśnięcia asysty technicznej',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                ))

            ->add('dokumentacjaFormalna', 'choice', array(
                'choices' => array('protok. odbioru' => 'protok. odbioru', "SIWZ" => "SIWZ", "umowa" => "umowa", "inna" => "inna")
            ))
            ->add('dokumentacjaProjektowoTechniczna', 'choice', array(
                'choices' => array('brak' => 'brak', "inna" => "inna", "powdrożeniowa" => "powdrożeniowa", "proj. techniczny" => "proj. techniczny", "raport z analizy" => "raport z analizy", "specyf. wymagań" => "specyf. wymagań")
            ))
            ->add('technologia')
            ->add('testyBezpieczenstwa')
            ->add('testyWydajnosciowe')
            ->add('dataZleceniaOstatniegoPrzegladuUprawnien', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zlecenia ostatniego przeglądu uprawnień',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ))
            ->add('interwalPrzegladuUprawnien')
            ->add('dataZleceniaOstatniegoPrzegladuAktywnosci', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zlecenia ostatniego przeglądu aktywności',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ))
            ->add('interwalPrzegladuAktywnosci')
            ->add('dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zlecenia ostatniej zmiany haseł',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ))
            ->add('interwalZmianyHaselKontaAdministracyjnychISerwisowych')
            ->add('dataUtworzeniaZasobu', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data utworzenia zasobu',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                ))
            ->add('dataZmianyZasobu', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data ostatniej zmiany zasobu',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                ))
            ->add('dataUsunieciaZasobu', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
                    'label' => 'Data usunięcia zasobu',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\Zasoby'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_zasoby';
    }
}
