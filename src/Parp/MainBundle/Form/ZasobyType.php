<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZasobyType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nazwa')
            ->add('opis', 'hidden')//jest drugie pole opis z importu ecm
            ->add('biuro', 'hidden')
            ->add('wlascicielZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInputUsers'), 'required' => false
            ))
            ->add('administratorZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInputUsers'), 'required' => false
            ))
            ->add('administratorTechnicznyZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInputUsers'), 'required' => false
            ))
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
            ->add('modulFunkcja')
            ->add('poziomDostepu')
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
//                'widget' => 'single_text',
                    'label' => 'Data wygaśnięcia asysty technicznej',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
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
