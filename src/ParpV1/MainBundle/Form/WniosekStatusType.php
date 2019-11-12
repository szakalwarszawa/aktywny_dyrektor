<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\WniosekStatus;

class WniosekStatusType extends AbstractType
{
    protected $role = array(
                    'wnioskodawca' => 'wnioskodawca',
                    'podmiot' => 'podmiot wniosku',
                    'przelozony' => 'przełożony pracownika (dyrektor)',
                    'ibi' => 'ibi - inspektor bezpieczenstwa informacji',
                    'wlasciciel' => 'właściciel zasobu',
                    'administrator' => 'administrator zasobu',
                    'techniczny' => 'administrator techniczny zasobu (wylacznie pracownicy BI)',
                    'administratorZasobow' => 'administrator rejestru zasobów (PARP_ADMIN_REJESTRU_ZASOBOW)',
                    'nadzorcaDomen' => 'nadzorca domen',
                );
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $transformer = new \ParpV1\MainBundle\Form\DataTransformer\StringToArrayTransformer();
        $builder
            ->add('nazwa')
            ->add('typWniosku', ChoiceType::class, [
                'choices' => [
                    'Wniosek o nadanie uprawnień' => 'wniosekONadanieUprawnien',
                    'Wniosek o utworzenie zasobu' => 'wniosekOUtworzenieZasobu'
                    ]
                ])

            ->add('nazwaSystemowa')
            ->add('finished')
            ->add('opis')
            ->add($builder->create('viewers', ChoiceType::class, array(
                'multiple' => true,
                'attr' => array(
                    'class' => 'select2'
                ),
                'choices' => array_flip($this->role),
                'required' => false,
                'label' => 'Kto widzi wniosek o tym statusie'
            ))->addModelTransformer($transformer))
            ->add($builder->create('editors', ChoiceType::class, array(
                'multiple' => true,
                'attr' => array(
                    'class' => 'select2'
                ),
                'choices' => array_flip($this->role),
                'required' => false,
                'label' => 'Kto może edytować wniosek o tym statusie'
            ))->addModelTransformer($transformer))

        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => WniosekStatus::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_wniosekstatus';
    }
}
