<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Query\Mysql\GroupConcat;

/**
 * WniosekNadanieOdebranieZasobowRepository.
 */
class WniosekNadanieOdebranieZasobowRepository extends EntityRepository
{
    /**
     * Zwraca listę wniosków danego typu np. oczekujace lub wszystkie.
     *
     * @param string $typWniosku
     *
     * @return array
     */
    public function findWnioskiDoZakladki(string $typWniosku, array $zastepstwa): array
    {
        $queryBuilder = $this->createQueryBuilder('wno');
        $queryBuilder
            ->select(
                'wno.id, w.numer as numerWniosku,
                s.nazwa as statusWniosku,
                wno.odebranie as odebranie,
                w.createdBy as utworzonyPrzez,
                w.createdAt as utworzonyDnia,
                w.lockedBy as zablokowanyPrzez,
                wno.pracownicy,
                GROUP_CONCAT(DISTINCT e.samaccountname SEPARATOR \', \') as edytorzy,
                wno.zasoby'
            )
            ->leftJoin('wno.userZasoby', 'uz')
            ->leftJoin('wno.wniosek', 'w')
            ->leftJoin('w.viewers', 'v')
            ->leftJoin('w.editors', 'e')
            ->leftJoin('w.status', 's')
        ;

        if ($typWniosku !== WniosekNadanieOdebranieZasobow::WNIOSKI_WSZYSTKIE) {
            $queryBuilder->andWhere('v.samaccountname IN (\''.implode('\',\'', $zastepstwa).'\')');
        }

        $statusyZamkniete = [
            '08_ROZPATRZONY_NEGATYWNIE',
            '07_ROZPATRZONY_POZYTYWNIE',
            '11_OPUBLIKOWANY',
            '102_ODEBRANO_ADMINISTRACYJNIE',
            '101_ANULOWANO_ADMINISTRACYJNIE',
            '10_PODZIELONY'
        ];

        switch ($typWniosku) {
            case WniosekNadanieOdebranieZasobow::WNIOSKI_W_TOKU:
                $warunek = 's.nazwaSystemowa NOT IN (\''.implode('\',\'', $statusyZamkniete).'\')';
                $queryBuilder->andWhere($warunek);
                $queryBuilder->andWhere('wno.id NOT in (select wn.id from ParpMainBundle:WniosekNadanieOdebranieZasobow wn left join wn.wniosek w2 left join w2.editors e2 where e2.samaccountname IN (\''.
                    implode('\',\'', $zastepstwa).
                    '\'))');
                break;
            case WniosekNadanieOdebranieZasobow::WNIOSKI_OCZEKUJACE:
                $queryBuilder->andWhere('e.samaccountname IN (\''.implode('\',\'', $zastepstwa).'\')');
                break;
            case WniosekNadanieOdebranieZasobow::WNIOSKI_ZAKONCZONE:
                $queryBuilder->andWhere('s.nazwaSystemowa IN (\''.implode('\',\'', $statusyZamkniete).'\')');
                break;
            case WniosekNadanieOdebranieZasobow::WNIOSKI_WSZYSTKIE:
                $w = 's.nazwaSystemowa IN (\''.implode('\',\'', $statusyZamkniete).'\', \'00_TWORZONY\')';
                break;
        }

        $queryBuilder->addGroupBy('wno.id');

        return $queryBuilder
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
