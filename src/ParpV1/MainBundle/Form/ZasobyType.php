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
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $adminiMulti = false;

        $ldap = $options['ldap_service'];
        $admini = $ldap->getAdministratorzyZasobow();

        $zablokujPolaPozaPoziomModul = $options['nie_moze_edytowac'] && $options['czy_wlasciciel_lub_powiernik'];

        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            //->add('id')
            ->add('zasobSpecjalny', CheckboxType::class, array(
                'label' => 'Zasób specjany',
                'mapped' => true,
                'required' => false,
            ))
            ->add('nazwa', TextType::class, ['label' => $options['nazwa_label'], 'attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('opis', HiddenType::class)//jest drugie pole opis z importu ecm
            ->add('biuro', HiddenType::class);
        if ($adminiMulti) {
            $builder->add($builder->create('wlascicielZasobu', ChoiceType::class, array(
                'choices' => $ldap->getWlascicieleZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul)
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
                'attr' => array('class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul)
            ));
        }

        $builder->add($builder->create('powiernicyWlascicielaZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAllFromADforCombo(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))
            ->add($builder->create('administratorTechnicznyZasobu', ChoiceType::class, array(
                'choices' => $ldap->getAdministratorzyTechniczniZasobow(),
                'multiple' => true,
                'required' => false,
                'attr' => array('class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul)
            ))->addModelTransformer($transformer))


            ->add('uzytkownicy', ChoiceType::class, array(
                'choices' => array('PARP' => 'PARP', "P/Z" => "P/Z", "Zewnętrzni" => "Zewnętrzni"),
                'attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]
            ))
            ->add('daneOsobowe', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])
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
                'attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]
            ))
            ->add('miejsceInstalacji', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('opisZasobu', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('modulFunkcja', TextType::class, ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('poziomDostepu', TextType::class, ['required' => false, 'attr' => ['class' => 'tagAjaxInput']])
            ->add('grupyAD', TextType::class, array(
                'attr' => array('class' => 'tagAjaxInput', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul), 'required' => false,
            ))


            ->add('dataZakonczeniaWdrozenia', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'placeholder' => 'wpisz tle grup AD ile poziomo dostepu',
                        'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul
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
                'attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]
            ))
            ->add('nazwaWykonawcy', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])
            ->add('asystaTechniczna', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])
            ->add('dataWygasnieciaAsystyTechnicznej', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul
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
                'attr' => ['class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul],
                'choices' => array('protok. odbioru' => 'protok. odbioru', "SIWZ" => "SIWZ", "umowa" => "umowa", "inna" => "inna")
            ))->addModelTransformer($transformer));
            $builder->add($builder->create('dokumentacjaProjektowoTechniczna', ChoiceType::class, array(
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select2', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul],
                'choices' => array('brak' => 'brak', "inna" => "inna", "powdrożeniowa" => "powdrożeniowa", "proj. techniczny" => "proj. techniczny", "raport z analizy" => "raport z analizy", "specyf. wymagań" => "specyf. wymagań")
            ), ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])->addModelTransformer($transformer));


            $builder->add('technologia', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('testyBezpieczenstwa', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])
            ->add('testyWydajnosciowe', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul]])
            ->add('dataZleceniaOstatniegoPrzegladuUprawnien', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul, 'disabled' => $zablokujPolaPozaPoziomModul
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
            ->add('interwalPrzegladuUprawnien', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('dataZleceniaOstatniegoPrzegladuAktywnosci', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul
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
            ->add('interwalPrzegladuAktywnosci', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul
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
            ->add('interwalZmianyHaselKontaAdministracyjnychISerwisowych', null, ['attr' => ['readonly' => $zablokujPolaPozaPoziomModul]])
            ->add('dataUtworzeniaZasobu', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul
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
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul
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
                        'class' => 'form-control datepicker', 'readonly' => $zablokujPolaPozaPoziomModul
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
            'ldap_service' => null,
            'nazwa_label' => 'Nazwa',
            'nie_moze_edytowac' => false,
            'czy_wlasciciel_lub_powiernik' => false,
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
