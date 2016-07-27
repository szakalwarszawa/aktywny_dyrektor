<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WniosekUtworzenieZasobuType extends AbstractType
{
    
    protected $ADUsers;
    protected $hideCheckboxes;
    protected $typ;
    protected $entity;
    protected $nazwaLabel;
    protected $ADManagers;
    protected $container;
    protected $readonly;
    
    public function __construct($ADUsers, $ADManagers, $typ, $entity, $container, $readonly = false){
        $this->ADUsers = $ADUsers;
        $this->ADManagers = $ADManagers;
        $this->hideCheckboxes = $typ != "";
        $this->typ = $typ;
        $this->entity = $entity;
        $this->nazwaLabel = $typ == "nowy" ? "Proponowana nazwa" : "Nazwa";
        $this->container = $container;
        $this->readonly = $readonly;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('wniosek', new \Parp\MainBundle\Form\WniosekType($this->ADUsers), array(
                'label'=>false, 'data_class' => 'Parp\MainBundle\Entity\Wniosek')
            )
            //->add('deletedAt')
            ->add('imienazwisko', 'text', ['label' => 'Imię i nazwisko', 'attr' => ['readonly' => true]])
            ->add('login', 'text', ['attr' => ['readonly' => true]])
            ->add('departament', 'text', ['attr' => ['readonly' => true]])
            ->add('stanowisko', 'text', ['attr' => ['readonly' => true]])
            ->add('telefon')
            ->add('nrpokoju', 'text', ['required' => false, 'label' => 'Numer pokoju'])
            ->add('email')
            //->add('proponowanaNazwa')
                      
            //->add('zasob')
            ->add('zmienionePola', 'text', ['attr' => ['readonly' => true]])
            ->add('zrealizowany', 'hidden');
           
           
            if($this->hideCheckboxes){
                //die('chowam czeki');
                $entity = $this->entity;
                if($entity->getTypWnioskuDoRejestru())
                    $builder->add('typWnioskuDoRejestru', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'do Rejestru']);
                if($entity->getTypWnioskuDoUruchomienia())
                    $builder->add('typWnioskuDoUruchomienia', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'do utworzenia (uruchomienia) w infrastrukturze PARP']);
                if($entity->getTypWnioskuZmianaInformacji())
                    $builder->add('typWnioskuZmianaInformacji', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'informacji o zarejestrowanym zasobie']);
                if($entity->getTypWnioskuZmianaWistniejacym())
                    $builder->add('typWnioskuZmianaWistniejacym', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'w istniejącym zasobie']);
                if($entity->getTypWnioskuWycofanie())
                    $builder->add('typWnioskuWycofanie', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'z Rejestru']);
                if($entity->getTypWnioskuWycofanieZinfrastruktury())
                    $builder->add('typWnioskuWycofanieZinfrastruktury', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'z infrastruktury PARP']) ;
            }else{
                
                $builder->add('typWnioskuDoRejestru', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'do Rejestru']);

                $builder->add('typWnioskuDoUruchomienia', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'do utworzenia (uruchomienia) w infrastrukturze PARP']);

                $builder->add('typWnioskuZmianaInformacji', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'informacji o zarejestrowanym zasobie']);

                $builder->add('typWnioskuZmianaWistniejacym', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'w istniejącym zasobie']);

                $builder->add('typWnioskuWycofanie', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'z Rejestru']);

                $builder->add('typWnioskuWycofanieZinfrastruktury', ($this->hideCheckboxes ? 'hidden' : 'checkbox'), ['required' => false, 'label' => 'z infrastruktury PARP']) ;
            }
           
            
            
            
            //die(".".$this->typ); 
            if($this->typ == "zmiana"){
                if($this->entity->getId()){
                    $builder->add('zmienianyZasob', 'text', ['mapped' => false, 'attr' => ['readonly' => true], 'data' => $this->entity->getZmienianyZasob()->getNazwa()]);
                }else{
                    $builder->add('zmienianyZasob', 'entity', array(
                        'mapped' => true,
                       'label'=>"Wybierz zasób", 'class' => 'Parp\MainBundle\Entity\Zasoby',
                       'attr' => ['class' => 'select2', 'style' => "width:100%"],
                       'query_builder' => function( $er) {
                                return $er->createQueryBuilder('u')
                                    ->andWhere('u.published = 1');
                                    //->orderBy('u.name', 'ASC');//->findByPublished(1);
                             }
                        )
                    );
                }
            }else{
                $builder->add('zmienianyZasob', 'hidden', ['attr' => ['class' => 'form-item']]);
            }   
            if($this->typ == "kasowanie"){
                $builder->add('zmienianyZasob', 'entity', array(
                    'query_builder' => function( $er) {
                        return $er->createQueryBuilder('u')
                            ->andWhere('u.published = 1');
                            //->orderBy('u.name', 'ASC');//->findByPublished(1);
                    },
                   'label'=>"Wybierz zasób do skasowania", 'class' => 'Parp\MainBundle\Entity\Zasoby', 'attr' => ['class' => 'select2', 'style' => "width:100%"])
                );
                $builder->add('zasob', 'hidden', ['attr' => ['class' => 'form-item']]);
            }else{
                $builder->add('zasob', new \Parp\MainBundle\Form\ZasobyType($this->container, $this->nazwaLabel), array(
                   'label'=>false, 'data_class' => 'Parp\MainBundle\Entity\Zasoby')
                );
            }             

/*
        switch($this->typ){
            case "nowy":
                $builder->add('zasobDoSkasowania', 'hidden');
                
                break;
            case "zmiana":
                $builder->add('zasobDoSkasowania', 'hidden');
                $builder->add('zasob', new \Parp\MainBundle\Form\ZasobyType("Proponowana nazwa"), array(
                   'label'=>false, 'data_class' => 'Parp\MainBundle\Entity\Zasoby')
                );
                break;
            case "kasowanie":
                $builder->add('zasob', 'hidden');
                $builder->add('zasobDoSkasowania', null, ['label' => 'Zasoby do skasowania', 'attr' => ['class' => 'select2', 'style' => 'width:100%;']])
                ;
            case "":
                $builder->add('zasob', 'hidden');
                $builder->add('zasobDoSkasowania', null, ['label' => 'Zasoby do skasowania', 'attr' => ['class' => 'select2', 'style' => 'width:100%;']])
                ;
                
                break;
        }
*/
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Parp\MainBundle\Entity\WniosekUtworzenieZasobu',
            'disabled' => $this->readonly,
            'attr' => ['readonly' => $this->readonly]
        ));
    }
/*
    public function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['readonly' => $this->readonly],  
        ]);
    }
*/
    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_wniosekutworzeniezasobu';
    }
}
