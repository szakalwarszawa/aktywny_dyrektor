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
            ->add('opis')
            ->add('biuro', 'hidden')
            ->add('wlascicielZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInput')
            ))
            ->add('administratorZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInput')
            ))
            ->add('administratorTechnicznyZasobu', 'text', array(
                'attr' => array('class' => 'tagAjaxInput')
            ))
            ->add('uzytkownicy', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzni" => "Zewnętrzni")
            ))
            ->add('daneOsobowe')
            ->add('komorkaOrgazniacyjna', 'entity', array(
                'class' => 'Parp\MainBundle\Entity\Departament',
                'choice_value' => function($dep){
                    return $dep->getName();
                }
            ))
            ->add('miejsceInstalacji')
            ->add('opisZasobu')
            ->add('modulFunkcja')
            ->add('poziomDostepu')
            ->add('grupyAD')
            ->add('dataZakonczeniaWdrozenia', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'label' => 'Data zmiany',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    
                ))
            ->add('wykonawca', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzny" => "Zewnętrzny")
            ))
            ->add('nazwaWykonawcy')
            ->add('asystaTechniczna')
            ->add('dataWygasnieciaAsystyTechnicznej', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    
                ))
            ->add('dokumentacjaFormalna')
            ->add('dokumentacjaProjektowoTechniczna')
            ->add('technologia')
            ->add('testyBezpieczenstwa')
            ->add('testyWydajnosciowe')
            ->add('dataZleceniaOstatniegoPrzegladuUprawnien', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    
                ))
            ->add('interwalPrzegladuUprawnien')
            ->add('dataZleceniaOstatniegoPrzegladuAktywnosci', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    
                ))
            ->add('interwalPrzegladuAktywnosci')
            ->add('dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    
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
