<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class WniosekUtworzenieZasobuType
 * @package ParpV1\MainBundle\Form
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
        $formPost = false;
        if (!empty($_POST)) {
            $formPost = true;
        }
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
            ->add('wniosekDomenowy', CheckboxType::class, $atrs)
            ->add('wniosek', new WniosekType($ADUsers), array(
                'label' => false, 'data_class' => 'ParpV1\MainBundle\Entity\Wniosek'))
            //->add('deletedAt')
            ->add('imienazwisko', TextType::class, ['label' => 'Imię i nazwisko', 'attr' => ['readonly' => true]])
            ->add('login', TextType::class, ['attr' => ['readonly' => true]])
            ->add('departament', TextType::class, ['attr' => ['readonly' => true]])
            ->add('stanowisko', TextType::class, ['attr' => ['readonly' => true]])
            ->add('telefon')
            ->add('nrpokoju', TextType::class, ['required' => false, 'label' => 'Numer pokoju'])
            ->add('email')
            //->add('proponowanaNazwa')

            //->add('zasob')
            ->add('zmienionePola', TextType::class, ['attr' => ['readonly' => true]])
            ->add('zrealizowany', HiddenType::class);

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
                'label' => false, 'data_class' => 'ParpV1\MainBundle\Entity\Zasoby', 'by_reference' => true,

                'cascade_validation' => true,
            ));

            $builder->add('zmienianyZasob', HiddenType::class, ['attr' => ['class' => 'form-item']]);
        } elseif ("zmiana" === $typ) {
            if ($entity->getId()) {
                $builder->add(
                    'zmienianyZasob',
                    TextType::class,
                    [
                        'mapped' => false,
                        'attr' => ['readonly' => true],
                        'data' => $entity->getZmienianyZasob()->getNazwa()
                    ]
                );
            } else {
                if ($formPost === false) {
                    $zasobyService = $container->get('zasoby_service');
                    $builder->add('zmienianyZasob', ChoiceType::class, array(
                        'mapped' => true,
                        'label' => "Wybierz zasób",
                        'choices' => $zasobyService->findZasobyDlaUsera($options['user']),
                        'attr' => array(
                            'class' => 'select2'
                        ),
                    ));
                } elseif ($formPost !== false) {
                    $builder->add('zmienianyZasob', EntityType::class, array(
                        'mapped' => true,
                        'label' => "Wybierz zasób",
                        'class' => Zasoby::class,
                        'attr' => array(
                            'class' => 'select2'
                        ),
                        'query_builder' => function ($er) {
                            return $er->createQueryBuilder('u')
                                ->andWhere('u.published = 1');
                        }
                    ));
                }
            }
            $builder->add('zasob', new ZasobyType($container, $nazwaLabel), array(
                'label' => false, 'data_class' => 'ParpV1\MainBundle\Entity\Zasoby', 'by_reference' => true,

            ));
        } elseif ("kasowanie" === $typ) {
            $route = $container->get('request')->get('_route');
            if ($formPost === false && $route !== 'wniosekutworzeniezasobu_show') {
                $zasobyService = $container->get('zasoby_service');
                $builder->add('zmienianyZasob', ChoiceType::class, array(
                    'mapped' => true,
                    'label' => "Wybierz zasób",
                    'choices' => $zasobyService->findZasobyDlaUsera($options['user']),
                    'attr' => array(
                        'class' => 'select2'
                    ),
                ));
            } else {
                $builder->add('zmienianyZasob', EntityType::class, array(
                                'mapped' => true,
                                'label' => "Wybierz zasób",
                                'class' => Zasoby::class,
                                'attr' => array(
                                    'class' => 'select2'
                                ),
                                'query_builder' => function ($er) {
                                    return $er->createQueryBuilder('u')
                                        ->andWhere('u.published = 1');
                                }
                ));
            }

            $builder->add('zasob', HiddenType::class, ['attr' => ['class' => 'form-item']]);
        } else {
            throw new UnexpectedTypeException('Nieznany typ '. $typ, null);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'ParpV1\MainBundle\Entity\WniosekUtworzenieZasobu',
            'ADUsers' => null,
            'ADManagers' => null,
            'hideCheckboxes' => false,
            'user' => null,
        ]);

        $resolver->setRequired([
            'container',
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_wniosekutworzeniezasobu';
    }
}
