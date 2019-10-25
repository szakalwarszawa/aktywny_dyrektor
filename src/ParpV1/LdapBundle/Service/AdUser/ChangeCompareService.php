<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser;

use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\Position;
use Doctrine\ORM\EntityManager;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\LdapBundle\Constants\AllowedToFetchAttributes;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\LdapBundle\DataCollection\Change\Changes\AdUserChange;
use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\LdapBundle\Constants\NullableAttributes;

/**
 * Klasa porównująca zmiany z obiektu Entry względem AD.
 */
class ChangeCompareService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Określenie atrybutów które mają być sprwadzone czy zaszła na nich zmiana.
     *
     * @var array
     */
    private $specifiedAttributes = [];

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Zwraca jakie atrybuty zostały zmienione.
     * Porównanie Entry <=> AD
     * Puste pola w entry nie są zaliczane jako zmiana chyba, że pole może być nullem (NullableAttributes).
     *
     * @param Entry $entry - oczekujące zmiany
     * @param array $deficientAdUser - okrojona tablica użytkownika bezpośrednio z AD
     *
     * @return ArrayCollection - zmienione atrybuty oraz ich wartości
     */
    public function compareByEntry(Entry $entry, array $deficientAdUser): ArrayCollection
    {
        $possibleChangeKeys = $this->findEntryAdAttributes();
        $changeCollector = new ArrayCollection();
        foreach ($possibleChangeKeys as $changeKey) {
            $valueGetter = AttributeGetterSetterHelper::get($changeKey);
            if ($entry->$valueGetter() !== $deficientAdUser[$changeKey]) {
                if (null !== $entry->$valueGetter() || in_array($changeKey, NullableAttributes::getAll())) {
                    $change = new AdUserChange($deficientAdUser[$changeKey], $entry->$valueGetter(), $changeKey);
                    $changeCollector->add($change);
                }
            }
        }

        if (!empty($this->specifiedAttributes)) {
            return $this->removeNotSpecifiedChanges($changeCollector);
        }

        return $changeCollector;
    }

    /**
     * Usuwa z listy zmian niechciane elementy (nieokreślone w $this->specifiedAttributes)
     *
     * @param ArrayCollection $changes
     *
     * @return ArrayCollection
     */
    private function removeNotSpecifiedChanges(ArrayCollection $changes): ArrayCollection
    {
        foreach ($changes as $key => $change) {
            if (!in_array($change->getTarget(), $this->specifiedAttributes, true)) {
                $changes->remove($key);
                continue;
            }

            if (method_exists($change->getNew(), 'getName')) {
                if ($change->getNew()->getName() === $change->getOld()) {
                    $changes->remove($key);
                    continue;
                }

                $change
                    ->setNew($change->getNew()->getName())
                ;
            }

            if ($change->getTarget() === 'title') {
                if ($this->czyStanowiskoZtejSamejGrupy($change->getOld(), $change->getNew())) {
                    $changes->remove($key);
                }
                continue;
            }
        }

        return $changes;
    }

    /**
     * Zwraca jakie atrybuty zostały zmienione.
     * Porównanie Tablica <=> AD
     *
     * @param array $changesArray - zmiany na użytkowniku
     * @param array $deficientAdUser - okrojona tablica użytkownika bezpośrednio z AD
     *
     * @return ArrayCollection - zmienione atrybuty oraz ich wartości
     */
    public function compareByArray(array $changesArray, array $deficientAdUser)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setRequired(AdUserConstants::LOGIN)
            ->setDefaults(array_fill_keys(AllowedToFetchAttributes::getAll(), null))
        ;

        $options = $optionsResolver->resolve($changesArray);
        $changeCollector = new ArrayCollection();
        foreach ($options as $key => $value) {
            if ($value !== $deficientAdUser[$key]) {
                if (null !== $value || in_array($key, NullableAttributes::getAll())) {
                    $change = new AdUserChange($deficientAdUser[$key], $value, $key);
                    $changeCollector->add($change);
                }
            }
        }

        if (!empty($this->specifiedAttributes)) {
            return $this->removeNotSpecifiedChanges($changeCollector);
        }

        return $changeCollector;
    }

    /**
     * Znajduje w metadanych klasy Entry kolumny mające swoje odpowiedniki w AD.
     *
     * @return array
     */
    private function findEntryAdAttributes(): array
    {
        $entityManager = $this->entityManager;
        $entryClassColumns = $entityManager
            ->getClassMetadata(Entry::class)
            ->getColumnNames()
        ;

        $adAttributes = AllowedToFetchAttributes::getAll();

        return array_intersect($entryClassColumns, $adAttributes);
    }

    /**
     * Get specifiedAttributes
     *
     * @return array
     */
    public function getSpecifiedAttributes(): array
    {
        return $this->specifiedAttributes;
    }

    /**
     * Set specifiedAttributes
     *
     * @param array $specifiedAttributes
     *
     * @return ChangeCompareService
     */
    public function setSpecifiedAttributes(array $specifiedAttributes): ChangeCompareService
    {
        $this->specifiedAttributes = $specifiedAttributes;

        return $this;
    }

    /**
     * Porównujemy czy stnaowiska należą do tej samej grupy
     *
     * @param string $stanowiskoStare
     * @param string $stanowiskoNowe
     *
     * @return bool
     */
    public function czyStanowiskoZtejSamejGrupy(string $stanowiskoStare, string $stanowiskoNowe): bool
    {
        $stanowiska = $this->entityManager->getRepository(Position::class)->findBy([
            'name' => [$stanowiskoStare, $stanowiskoNowe],
        ]);

        if (count($stanowiska) !== 2) {
            return false;
        }

        return ($stanowiska[0]->getGroup() === $stanowiska[1]->getGroup());
    }
}
