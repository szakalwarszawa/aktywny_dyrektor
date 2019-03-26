<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser;

use ParpV1\MainBundle\Entity\Entry;
use Doctrine\ORM\EntityManager;
use ReflectionClass;
use ParpV1\LdapBundle\Constraints\Attributes;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;

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
     * @return array - zmienione atrybuty oraz ich wartości
     */
    public function compare(Entry $entry, array $deficientAdUser): array
    {
        $possibleChangeKeys = $this->findEntryAdAttributes();

        $changes = [];
        foreach ($possibleChangeKeys as $changeKey) {
            $valueGetter = AttributeGetterSetterHelper::get($changeKey);
            if ($entry->$valueGetter() !== $deficientAdUser[$changeKey] && null !== $entry->$valueGetter()) {
                $changes[$changeKey] = [
                    'old' => $deficientAdUser[$changeKey],
                    'new' => $entry->$valueGetter(),
                ];
            }
        }

        return $changes;
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

        $adAttributes = (new ReflectionClass(Attributes::class))
            ->getConstants()
        ;

        return array_intersect($entryClassColumns, $adAttributes);
    }
}
