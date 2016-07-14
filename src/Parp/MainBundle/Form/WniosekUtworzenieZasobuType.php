<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WniosekUtworzenieZasobuType extends AbstractType
{
    
    protected $ADUsers;
    
    public function __construct($ADUsers){
        $this->ADUsers = $ADUsers;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('wniosek', new \Parp\MainBundle\Form\WniosekType($this->ADUsers), array(
                'data_class' => 'Parp\MainBundle\Entity\Wniosek')
            )
            //->add('deletedAt')
            ->add('imienazwisko', 'text', ['attr' => ['readonly' => true]])
            ->add('login', 'text', ['attr' => ['readonly' => true]])
            ->add('departament', 'text', ['attr' => ['readonly' => true]])
            ->add('stanowisko', 'text', ['attr' => ['readonly' => true]])
            ->add('telefon')
            ->add('nrpokoju')
            ->add('email')
            ->add('proponowanaNazwa')
            ->add('typWnioskuDoRejestru', 'checkbox', ['label' => 'do Rejestru'])
            ->add('typWnioskuDoUruchomienia', 'checkbox', ['label' => 'do utworzenia (uruchomienia) w infrastrukturze PARP'])
            ->add('typWnioskuZmianaInformacji', 'checkbox', ['label' => 'informacji o zarejestrowanym zasobie'])
            ->add('typWnioskuZmianaWistniejacym', 'checkbox', ['label' => 'w istniejącym zasobie'])
            ->add('typWnioskuWycofanie', 'checkbox', ['label' => 'z Rejestru'])
            ->add('typWnioskuWycofanieZinfrastruktury', 'checkbox', ['label' => 'z infrastruktury PARP'])            
            //->add('zasob')
            ->add('zrealizowany')
            ->add('zasob', new \Parp\MainBundle\Form\ZasobyType(), array(
                'data_class' => 'Parp\MainBundle\Entity\Zasoby')
            )
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\WniosekUtworzenieZasobu'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_wniosekutworzeniezasobu';
    }
}
