<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ZasobyType extends AbstractType
{
    protected $nazwaLabel;

    protected $container;
    protected $zablokujPolaPozaPoziomModul = false;

    public function __construct($container, $nazwaLabel = "Nazwa", $niemozeEdytowac = false, $czyJestWlascicielemLubPowiernikiem = false)
    {
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

        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            //->add('id')
            ->add('zasobSpecjalny', CheckboxType::class, array(
                'label' => 'Zasób specjany',
                'mapped' => true,
                'required' => false,
            ))
            ->add('nazwa', TextType::class, ['label' => $this->nazwaLabel, 'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('opis', HiddenType::class)//jest drugie pole opis z importu ecm
            ->add('biuro', HiddenType::class);
        if ($adminiMulti) {
            $builder->add($builder->create('wlascicielZasobu', ChoiceType::class, array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer));
        } else {
            $builder->add('wlascicielZasobu', ChoiceType::class, array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => false,
                'placeholder' => 'Wybierz wartość',
                'constraints' => array(
                    new Assert\NotBlank(array(
                        'message' => 'Wybór właściciela zasobu jest obligatoryjny. Jeśli na liście wyboru nie ' .
                        'ma odpowiedniej osoby należy skontaktować się z Hubertem Góreckim lub Jarosławem Bednarczykiem'
                    )),
                ),
                'error_bubbling' => true,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ));
        }

        $builder->add($builder->create('powiernicyWlascicielaZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorTechnicznyZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAdministratorzyTechniczniZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))


            ->add('uzytkownicy', ChoiceType::class, array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzni" => "Zewnętrzni"),
                'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]
            ))
            ->add('daneOsobowe', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('komorkaOrgazniacyjna', EntityType::class, array(
                'class' => 'ParpV1\MainBundle\Entity\Departament',
                'choice_value' => function ($dep) {
                    return $dep ? (is_object($dep) ? $dep->getName() : $dep) : "___BRAK___";
                },
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
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
            ->add('modulFunkcja', TextType::class, ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('poziomDostepu', TextType::class, ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('grupyAD', TextType::class, array(
                'attr' => array('class' => 'tagAjaxInput', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul), 'required' => false,
            ))


            ->add('dataZakonczeniaWdrozenia', DateTimeType::class, array(
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
            ->add('wykonawca', ChoiceType::class, array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzny" => "Zewnętrzny"),
                'attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]
            ))
            ->add('nazwaWykonawcy', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('asystaTechniczna', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataWygasnieciaAsystyTechnicznej', DateTimeType::class, array(
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
            $builder->add($builder->create('dokumentacjaFormalna', ChoiceType::class, array(
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul],
                'choices' => array('protok. odbioru' => 'protok. odbioru', "SIWZ" => "SIWZ", "umowa" => "umowa", "inna" => "inna")
            ))->addModelTransformer($transformer));
            $builder->add($builder->create('dokumentacjaProjektowoTechniczna', ChoiceType::class, array(
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select2', 'readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul],
                'choices' => array('brak' => 'brak', "inna" => "inna", "powdrożeniowa" => "powdrożeniowa", "proj. techniczny" => "proj. techniczny", "raport z analizy" => "raport z analizy", "specyf. wymagań" => "specyf. wymagań")
            ), ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])->addModelTransformer($transformer));


            $builder->add('technologia', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul]])
            ->add('testyBezpieczenstwa', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('testyWydajnosciowe', null, ['attr' => ['readonly' => $this->zablokujPolaPozaPoziomModul, 'disabled' => $this->zablokujPolaPozaPoziomModul]])
            ->add('dataZleceniaOstatniegoPrzegladuUprawnien', DateTimeType::class, array(
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
            ->add('dataZleceniaOstatniegoPrzegladuAktywnosci', DateTimeType::class, array(
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
            ->add('dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych', DateTimeType::class, array(
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
            ->add('dataUtworzeniaZasobu', DateTimeType::class, array(
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
            ->add('dataZmianyZasobu', DateTimeType::class, array(
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
            ->add('dataUsunieciaZasobu', DateTimeType::class, array(
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Zasoby',
            //'inherit_data' => true,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_zasob';
    }
}
