<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser;

use ParpV1\MainBundle\Entity\Entry;
use Doctrine\ORM\EntityManager;
use ReflectionClass;
use ParpV1\LdapBundle\Constants\Attributes;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\LdapBundle\Constants\AllowedToFetchAttributes;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\LdapBundle\DataCollection\Change\Changes\AdUserChange;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Zwraca jakie atrybuty zostały zmienione.
     * Porównanie Entry <=> AD
     * Puste pola w entry nie są zaliczane jako zmiana.
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
            if ($entry->$valueGetter() !== $deficientAdUser[$changeKey] && null !== $entry->$valueGetter()) {
                $change = new AdUserChange($deficientAdUser[$changeKey], $entry->$valueGetter(), $changeKey);
                $changeCollector->add($change);
            }
        }

        return $changeCollector;
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
            if ($value !== $deficientAdUser[$key] && null !== $value ) {
                $change = new AdUserChange($deficientAdUser[$key], $value, $key);
                $changeCollector->add($change);
            }
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

    public function setCollector(Collector $collector)
    {
        $this->collector = $collector;
    }
}
