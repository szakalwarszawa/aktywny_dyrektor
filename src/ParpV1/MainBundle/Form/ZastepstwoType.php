<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\Zastepstwo;

class ZastepstwoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('opis', TextareaType::class, ['required' => true])
            ->add('ktoZastepuje', ChoiceType::class, array(
                'choices' => array_flip($options['ad_users']),
                'required' => true,
                'label' => 'Kto zastępuje',
                'attr' => array(
                    'class' => 'select2'
                )
            ));

        if (in_array("PARP_ADMIN", $options['current_user']->getRoles())
            || in_array("PARP_ADMIN_ZASTEPSTW", $options['current_user']->getRoles())) {
            $builder->add('kogoZastepuje', ChoiceType::class, array(
                'choices' => array_flip($options['ad_users']),
                'required' => true,
                'label' => 'Kogo zastępuje',
                'attr' => array(
                    'class' => 'select2'
                )
            ));
        } elseif (in_array("PARP_DB_ZASTEPSTWA", $options['current_user']->getRoles())) {
            $builder->add('kogoZastepuje', ChoiceType::class, array(
                    'choices' => array_flip($options['ad_users']),
                    'required' => true,
                    'label' => 'Kogo zastępuje',
                    'attr' => array(
                        'class' => 'select2'
                    )
            ));
        } else {
            $builder->add('kogoZastepuje', TextType::class, array(
                'required' => true,
                'label' => 'Kogo zastępuje',
                'data' => $options['current_user']->getUsername(),
                'attr' => array(
                    'readonly' => true
                )
            ));
        }

        $builder->add('dataOd', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                    ),
                    'label' => 'Data od',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => true,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm'

                ))
            ->add('dataDo', DateTimeType::class, array(
                    'attr' => array(
                        'class' => 'form-control datetimepicker',
                    ),
                    'label' => 'Data do',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => true,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm'

                ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Zastepstwo::class,
            'current_user' => null,
            'ad_users' => array()
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_zastepstwo';
    }
}
