<?php

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\AuthBundle\Security\ParpUser;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\WniosekHistoriaStatusow;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\MainBundle\Constants\AccessLevelTypes;
use ParpV1\MainBundle\Entity\AccessLevelGroup;

class ZasobyService
{
    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Dostęp do zasobu specjalnego mają określone role/grupy/użytkownicy
     *
     * @param array $zasob
     * @param ParpUser $uzytkownik
     *
     * @return bool
     *
     */
    public function zasobSpecjalnyDostep(array $zasob, ParpUser $uzytkownik)
    {
        $bialaLista = array_unique(
            array_merge(
                explode(',', $zasob['wlascicielZasobu']),
                explode(',', $zasob['administratorZasobu']),
                explode(',', $zasob['administratorTechnicznyZasobu']),
                explode(',', $zasob['powiernicyWlascicielaZasobu'])
            )
        );

        if (in_array($uzytkownik->getUsername(), $bialaLista)) {
            return true;
        }

        if (in_array('PARP_ZASOBY_SPECJALNE', $uzytkownik->getRoles())) {
            return true;
        }

        if (in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $uzytkownik->getRoles())) {
            return true;
        }

        return false;
    }

    /**
     * Odnajduje poziomy dostępu dla zasobu na podstawie typu (grupa lub pojedyncze poziomy).
     *
     * @param array $params - klucze: resource (id zasobu), type (grupa/pojedynczy poziom)
     *
     * @return array
     */
    public function findAccessLevels(array $params): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired(['resource', 'type'])
            ->setAllowedTypes('resource', 'numeric')
            ->setAllowedTypes('type', 'numeric')
        ;
        $resolver->resolve($params);

        if (in_array((int) $params['type'], AccessLevelTypes::getTypes())) {
            $accessLevelType = (int) $params['type'];
            $entityManager = $this->entityManager;
            if (AccessLevelTypes::GROUP === $accessLevelType) {
                $groups = $entityManager
                    ->getRepository(AccessLevelGroup::class)
                    ->findAccessLevelGroups($params['resource'], true)
                ;

                return $groups;
            } elseif (AccessLevelTypes::SINGLE) {
                $zasob = $entityManager
                    ->getRepository(Zasoby::class)
                    ->findOneBy([
                        'id' => $params['resource']
                    ]);
                if (null !== $zasob) {
                    $tempArray = [];
                    foreach (explode(';', $zasob->getPoziomDostepu()) as $poziomDostepu) {
                        $tempArray[$poziomDostepu] = $poziomDostepu;
                    }
                    return $tempArray;
                }
            }
        }

        return [];
    }

    /**
     * Wyszukuje wszystkie zasoby które może widzieć aktualny użytkownik.
     *
     * @param ParpUser $uzytkownik
     *
     * @return array
     */
    public function findZasobyDlaUsera(ParpUser $uzytkownik)
    {
        $zasoby = $this
                ->entityManager
                ->getRepository(Zasoby::class)
                ->findListaZasobow(true);

        $zasobyPrzefiltrowane = array();
        foreach ($zasoby as $zasob) {
            if (true === $zasob['zasobSpecjalny']) {
                if (true === $this->zasobSpecjalnyDostep($zasob, $uzytkownik)) {
                    $zasobyPrzefiltrowane[$zasob['id']] = $zasob['nazwa'];
                }
            } elseif (true !== $zasob['zasobSpecjalny']) {
                $zasobyPrzefiltrowane[$zasob['id']] = $zasob['nazwa'];
            }
        }

        return $zasobyPrzefiltrowane;
    }

    /**
     * Wyszukuje aktywne wnioski dla użytkownika sprzed podanej daty
     * i po niej.
     *
     * @param string $user
     * @param \DateTime $date
     *
     * @return array
     */
    public function findAktywneWnioski($user, \DateTime $date)
    {
        $user = strToLower($user);
        $wnioskiLista = array();
        $userZasoby = $this
            ->entityManager
            ->getRepository(UserZasoby::class)
            ->findBy(array(
                'samaccountname' => $user,
                'czyAktywne' => true,
            ));

        foreach ($userZasoby as $zasob) {
            if (null === $zasob->getWniosek()) {
                continue;
            }
            $infoZasob = array (
                'user_zasoby_id' => $zasob->getId(),
                'zasob_id'       => $zasob->getZasobId(),
                'wniosek'        => $zasob->getWniosek()->getId(),
                'grupy_ad'       => $this->czyZasobMaGrupyAd($zasob)
            );
            $statusyWniosku = $zasob->getWniosek()->getWniosek()->getStatusy();
            if (empty($statusyWniosku)) {
                $wnioskiLista[$user]['bez_statusu'][] = $infoZasob;
            } else {
                if (true === $this->sprawdzStatusyPoDacie($statusyWniosku, $date)) {
                    $wnioskiLista[$user]['przed_data'][] = $infoZasob;
                } else {
                    $wnioskiLista[$user]['po_dacie'][] = $infoZasob;
                }
            }
        }

        return $wnioskiLista;
    }

    /**
     * Sprwadza czy istnieje jakiś status przed podaną datą.
     *
     * @param ArrayCollection $statusyWniosku
     * @param \DateTime $date
     *
     * @return bool
     */
    private function sprawdzStatusyPoDacie(ArrayCollection $statusyWniosku, \DateTime $date)
    {
        foreach ($statusyWniosku as $status) {
            if ($date > $status->getCreatedAt()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sprawdza czy podany Zasob ma grupy w AD.
     *
     * @param UserZasoby|Zasoby $obiekt
     *
     * @throws InvalidArgumentException gdy $obiekt jest innej klasy niż UserZasoby lub Zasoby
     * @throws EntityNotFoundException gdy nie odnaleziono obiektu Zasoby
     *
     * @return bool
     */
    public function czyZasobMaGrupyAd($obiekt)
    {
        if (!($obiekt instanceof UserZasoby || $obiekt instanceof Zasoby)) {
            throw new InvalidArgumentException('Oczekiwano obiektu klasy Zasoby lub UserZasoby.');
        }

        if ($obiekt instanceof UserZasoby) {
            $zasobId = $obiekt->getZasobId();
            $zasob = $this
                ->entityManager
                ->getRepository(Zasoby::class)
                ->findOneById($zasobId)
            ;

            if (null === $zasob) {
                throw new EntityNotFoundException();
            }
        }

        $grupyAd = $zasob->getGrupyAD();

        return empty($grupyAd)? false : true;
    }
}
