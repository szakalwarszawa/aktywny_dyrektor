<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('typWniosku', ChoiceType::class, ['choices' => ['wniosekONadanieUprawnien' => 'wniosek o Nadanie Uprawnień', 'wniosekOUtworzenieZasobu' => 'wniosek o utworzenie zasobu']])
            ->add('nazwaSystemowa')
            ->add('finished')
            ->add('opis')
            ->add($builder->create('viewers', 'choice', array(
                'multiple' => true,
                'attr' => array(
                    'class' => 'select2'
                ),
                'choices' => $this->role,
                'required' => false,
                'label' => 'Kto widzi wniosek o tym statusie'
            ))->addModelTransformer($transformer))
            ->add($builder->create('editors', 'choice', array(
                'multiple' => true,
                'attr' => array(
                    'class' => 'select2'
                ),
                'choices' => $this->role,
                'required' => false,
                'label' => 'Kto może edytować wniosek o tym statusie'
            ))->addModelTransformer($transformer))
            
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\WniosekStatus'
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
