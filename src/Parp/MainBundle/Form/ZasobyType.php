<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ZasobyType extends AbstractType
{
    protected $nazwaLabel;
    
    protected $container;
    protected $zablokujPolaPozaPoziomModul = false;
    
    public function __construct($container, $nazwaLabel = "Nazwa", $niemozeEdytowac = false, $czyJestWlascicielemLubPowiernikiem = false){
        $this->container = $container;
        $this->nazwaLabel = $nazwaLabel;
        $this->zablokujPolaPozaPoziomModul = $niemozeEdytowac && $czyJestWlascicielemLubPowiernikiem;
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
            //->add('id')
            ->add('nazwa', 'text', ['label' => $this->nazwaLabel, 'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('opis', 'hidden')//jest drugie pole opis z importu ecm
            ->add('biuro', 'hidden');
        if($adminiMulti){
            $builder->add($builder->create('wlascicielZasobu', 'choice', array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer));
        }else{
            $builder->add('wlascicielZasobu', 'choice', array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => false,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ));
        }
            
        $builder->add($builder->create('powiernicyWlascicielaZasobu', 'choice', array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorZasobu', 'choice', array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorTechnicznyZasobu', 'choice', array(
                'choices' => $ldap->getAdministratorzyTechniczniZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            
            
            ->add('uzytkownicy', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzni" => "Zewnętrzni"),
                'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]
            ))
            ->add('daneOsobowe', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('komorkaOrgazniacyjna', 'entity', array(
                'class' => 'Parp\MainBundle\Entity\Departament',
                'choice_value' => function($dep){
                    return $dep ? (is_object($dep) ? $dep->getName() : $dep) : "___BRAK___";
                },
                'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            ->andWhere('u.nowaStruktura = 1')
                            ->orderBy('u.name', 'ASC');
                },
                //dodac warunek ze tylko nowe departamenty
                'label' => 'Komórka organizacyjna',
                'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]
            ))
            ->add('miejsceInstalacji', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('opisZasobu', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('modulFunkcja', 'text', ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('poziomDostepu', 'text', ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('grupyAD', 'text', array(
                'attr' => array('class' => 'tagAjaxInput', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul), 'required' => false,
            ))
            
            
            ->add('dataZakonczeniaWdrozenia', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'placeholder' => 'wpisz tle grup AD ile poziomo dostepu',
                        'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul
                    ),
                    'label' => 'Data zakończenia wdrożenia',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                    
                ))
            ->add('wykonawca', 'choice', array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzny" => "Zewnętrzny"),
                'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]
            ))
            ->add('nazwaWykonawcy', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('asystaTechniczna', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataWygasnieciaAsystyTechnicznej', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul
                    ),
                    'label' => 'Data wygaśnięcia asysty technicznej',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'widget' => 'single_text'
                ));
            $builder->add($builder->create('dokumentacjaFormalna', 'choice', array(
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul],
                'choices' => array('protok. odbioru' => 'protok. odbioru', "SIWZ" => "SIWZ", "umowa" => "umowa", "inna" => "inna")
            ))->addModelTransformer($transformer));
            $builder->add($builder->create('dokumentacjaProjektowoTechniczna', 'choice', array(
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul],
                'choices' => array('brak' => 'brak', "inna" => "inna", "powdrożeniowa" => "powdrożeniowa", "proj. techniczny" => "proj. techniczny", "raport z analizy" => "raport z analizy", "specyf. wymagań" => "specyf. wymagań")
            ), ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])->addModelTransformer($transformer));
            
            
            $builder->add('technologia', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('testyBezpieczenstwa', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('testyWydajnosciowe', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataZleceniaOstatniegoPrzegladuUprawnien', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul
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
            ->add('interwalPrzegladuUprawnien', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataZleceniaOstatniegoPrzegladuAktywnosci', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul
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
            ->add('interwalPrzegladuAktywnosci', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul
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
            ->add('interwalZmianyHaselKontaAdministracyjnychISerwisowych', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataUtworzeniaZasobu', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul
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
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul
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
                        'class' => 'form-control datepicker', 'readonly' => $this->zablokujPolaPozaPoziomModul
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
            'data_class' => 'Parp\MainBundle\Entity\Zasoby',            
            //'inherit_data' => true,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_zasob';
    }
}
