<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use ParpV1\MainBundle\Entity\Wniosek;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Constants\TypWnioskuConstants;
use ParpV1\MainBundle\Entity\WniosekStatus;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

/**
 * Klasa formularza przekierowania wniosku
 */
class PrzekierowanieWnioskuFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Status',
                'data'  => $options['wniosek']
                    ->getStatus()
                    ->getId(),
                'choices' => $this
                    ->findDostepneStatusyWniosku($options['wniosek'], $options['entity_manager']),
            ])
            ->add('viewers', ChoiceType::class, [
                'required' => false,
                'label' => 'Viewers',
                'data' => array_flip($options['wniosek']->getViewersNames(true)),
                'choices' => array_flip($options['ad_users']),
                'multiple' => true,
                'expanded' => false
            ])
            ->add('editors', ChoiceType::class, [
                'required' => false,
                'label' => 'Editors',
                'choices' => array_flip($options['ad_users']),
                'data' => array_flip($options['wniosek']->getEditorsNames(true)),
                'multiple' => true,
                'expanded' => false
            ])
            ->add('powod', TextareaType::class, [
                'required' => false,
                'label' => 'Powód',
            ])
            ->add('zapisz', SubmitType::class)
            ->setMethod('POST')
        ;
    }

    /**
     * Znajduje statusy wniosku dostępne dla podanego typu.
     *
     * @param string $typWniosku
     * @param EntityManager $entityManager
     *
     * @return array
     */
    private function findDostepneStatusyWniosku(Wniosek $wniosek, EntityManager $entityManager): array
    {

        $typWniosku = $this->okreslTypWniosku($wniosek);
        if (TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW === $typWniosku) {
            $typWniosku = 'wniosekONadanieUprawnien';
        }
        if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
            $typWniosku = 'wniosekOUtworzenieZasobu';
        }

        $statusyEntities = $entityManager
            ->getRepository(WniosekStatus::class)
            ->findBy([
                'typWniosku' => $typWniosku
            ]);

        $statusy = [];
        foreach ($statusyEntities as $status) {
            $statusy[$status->getNazwa()] = $status->getId();
        }

        return $statusy;
    }

    /**
     * Określa typ wniosku.
     *
     * @param Wniosek
     *
     * @return string
     */
    private function okreslTypWniosku(Wniosek $wniosek): string
    {
        $typWniosku = $wniosek->getWniosekNadanieOdebranieZasobow() ?
            TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW :
            TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU;

        return $typWniosku;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'wniosek' => null,
            'ad_users' => []
        ]);

        $resolver->setRequired([
            'entity_manager'
        ]);

        $resolver
            ->setAllowedTypes('wniosek', Wniosek::class)
            ->setAllowedTypes('ad_users', 'array')
            ->setAllowedTypes('entity_manager', EntityManager::class)
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'parp_mainbundle_przekierowaniewniosku';
    }
}
