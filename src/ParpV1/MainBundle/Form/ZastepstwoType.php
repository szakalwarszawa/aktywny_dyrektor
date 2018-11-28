<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZastepstwoType extends AbstractType
{
    protected $ADUser;
    protected $ADUsers;
    
    public function __construct($ADUser, $ADUsers)
    {
        $this->ADUser = $ADUser;
        $this->ADUsers = $ADUsers;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('deletedAt')
            ->add('opis', TextareaType::class, ['required' => true]);
        $builder->add('ktoZastepuje', ChoiceType::class, array(
                'choices' => $this->ADUsers,
                'required' => true, 'label' => 'Kto zastępuje', 'attr' => array('class' => 'select2')));
        if (in_array("PARP_ADMIN", $this->ADUser->getRoles()) || in_array("PARP_ADMIN_ZASTEPSTW", $this->ADUser->getRoles())) {
            //PARP_ADMIN oraz PARP_ADMIN_ZASTEPSTW moga ustawic kogolowiek jako kogo zastepuja
            $builder->add('kogoZastepuje', ChoiceType::class, array(
                'choices' => $this->ADUsers,
                'required' => true, 'label' => 'Kogo zastępuje', 'attr' => array('class' => 'select2')));
        } elseif (in_array("PARP_DB_ZASTEPSTWA", $this->ADUser->getRoles())) {
            //PARP_DB_ZASTEPSTWA moga ustawic kogolowiek z DB jako kogo zastepuja
            $builder->add('kogoZastepuje', ChoiceType::class, array(
                    'choices' => $this->ADUsers,
                    'required' => true, 'label' => 'Kogo zastępuje', 'attr' => array('class' => 'select2')));
        } else {
            //reszta normalnych osob, ma ustawionego tylko siebie jako kogoZastepuje
            $builder->add('kogoZastepuje', TextType::class, array(
                'required' => true, 'label' => 'Kogo zastępuje', 'data' => $this->ADUser->getUsername(), 'attr' => array('readonly' => true)));
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
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Zastepstwo'
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
