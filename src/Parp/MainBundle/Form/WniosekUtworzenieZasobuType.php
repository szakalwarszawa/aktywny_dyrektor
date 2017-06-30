<?php

namespace Parp\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class WniosekUtworzenieZasobuType
 * @package Parp\MainBundle\Form
 */
class WniosekUtworzenieZasobuType extends AbstractType
{
    /**
     * WniosekUtworzenieZasobuType constructor.
     */
    public function __construct()
    {
        // $ADUsers, $ADManagers, $typ, $entity, $container, $readonly = false
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $builder->getData();
        $ADUsers = $options['ADUsers'];
        $hideCheckboxes = $options['hideCheckboxes'];
        $typ = $entity->getTyp();
        $container = $options['container']; // FIXME: Tu jest potrzebny tylko service LDAP!
        $nazwaLabel = $typ == "nowy" ? "Proponowana nazwa" : "Nazwa";

        $atrs = [
            'required' => false,
            'attr' => [],
            'label' => "Wniosek dotyczy domeny internetowej (jeśli tak, będzie musiał być zatwierdzony przez DKM)"
        ];

        if (!$entity->getWniosek()->czyWtrakcieTworzenia()) {
            $atrs['attr'] = ['readonly' => true, 'disabled' => true];
        }

        $builder
            ->add('wniosekDomenowy', 'checkbox', $atrs)
            ->add('wniosek', new WniosekType($ADUsers), array(
                'label' => false, 'data_class' => 'Parp\MainBundle\Entity\Wniosek'))
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

        if (true === $hideCheckboxes) {
            if ($entity->getTypWnioskuDoRejestru()) {
                $builder->add(
                    'typWnioskuDoRejestru',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'do Rejestru']
                );
            }
            if ($entity->getTypWnioskuDoUruchomienia()) {
                $builder->add(
                    'typWnioskuDoUruchomienia',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'do utworzenia (uruchomienia) w infrastrukturze PARP']
                );
            }
            if ($entity->getTypWnioskuZmianaInformacji()) {
                $builder->add(
                    'typWnioskuZmianaInformacji',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'informacji o zarejestrowanym zasobie']
                );
            }
            if ($entity->getTypWnioskuZmianaWistniejacym()) {
                $builder->add(
                    'typWnioskuZmianaWistniejacym',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'w istniejącym zasobie']
                );
            }
            if ($entity->getTypWnioskuWycofanie()) {
                $builder->add(
                    'typWnioskuWycofanie',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'z Rejestru']
                );
            }
            if ($entity->getTypWnioskuWycofanieZinfrastruktury()) {
                $builder->add(
                    'typWnioskuWycofanieZinfrastruktury',
                    ($hideCheckboxes ? 'hidden' : 'checkbox'),
                    ['required' => false, 'label' => 'z infrastruktury PARP']
                );
            }
        } else {
            $builder->add(
                'typWnioskuDoRejestru',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'do Rejestru']
            );

            $builder->add(
                'typWnioskuDoUruchomienia',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'do utworzenia (uruchomienia) w infrastrukturze PARP']
            );

            $builder->add(
                'typWnioskuZmianaInformacji',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'informacji o zarejestrowanym zasobie']
            );

            $builder->add(
                'typWnioskuZmianaWistniejacym',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'w istniejącym zasobie']
            );

            $builder->add(
                'typWnioskuWycofanie',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'z Rejestru']
            );

            $builder->add(
                'typWnioskuWycofanieZinfrastruktury',
                ($hideCheckboxes ? 'hidden' : 'checkbox'),
                ['required' => false, 'label' => 'z infrastruktury PARP']
            );
        }

        if ($typ == "nowy" || $typ == "") {
            $builder->add('zasob', new ZasobyType($container, $nazwaLabel), array(
                'label' => false, 'data_class' => 'Parp\MainBundle\Entity\Zasoby', 'by_reference' => true,

                'cascade_validation' => true,
            ));

            $builder->add('zmienianyZasob', 'hidden', ['attr' => ['class' => 'form-item']]);
        } elseif ("zmiana" === $typ) {
            if ($entity->getId()) {
                $builder->add(
                    'zmienianyZasob', 'text',
                    [
                        'mapped' => false,
                        'attr' => ['readonly' => true],
                        'data' => $entity->getZmienianyZasob()->getNazwa()
                    ]
                );
            } else {
                $builder->add('zmienianyZasob', 'entity', array(
                    'mapped' => true,
                    'label' => "Wybierz zasób", 'class' => 'Parp\MainBundle\Entity\Zasoby',
                    'attr' => ['class' => 'select2', 'style' => "width:100%"],
                    'query_builder' => function ($er) {
                        return $er->createQueryBuilder('u')
                            ->andWhere('u.published = 1');
                        //->orderBy('u.name', 'ASC');//->findByPublished(1);
                    }
                ));
            }
            $builder->add('zasob', new ZasobyType($container, $nazwaLabel), array(
                'label' => false, 'data_class' => 'Parp\MainBundle\Entity\Zasoby', 'by_reference' => true,

            ));
        } elseif ("kasowanie" === $typ) {
            $builder->add(
                'zmienianyZasob',
                'entity',
                [
                    'query_builder' => function ($er) {
                        return $er->createQueryBuilder('u')
                            ->andWhere('u.published = 1');
                        //->orderBy('u.name', 'ASC');//->findByPublished(1);
                    },
                    'label' => "Wybierz zasób do skasowania", 'class' => 'Parp\MainBundle\Entity\Zasoby',
                'attr' => ['class' => 'select2', 'style' => "width:100%"]
                ]
            );
            $builder->add('zasob', 'hidden', ['attr' => ['class' => 'form-item']]);
        } else {
            throw new UnexpectedTypeException('Nieznany typ '. $typ, null);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Parp\MainBundle\Entity\WniosekUtworzenieZasobu',
            'ADUsers' => null,
            'ADManagers' => null,
            'hideCheckboxes' => false,
        ]);

        $resolver->setRequired([
            'container',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parp_mainbundle_wniosekutworzeniezasobu';
    }
}
