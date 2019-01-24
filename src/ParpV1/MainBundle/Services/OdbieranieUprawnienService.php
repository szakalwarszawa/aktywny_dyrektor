<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;
use Doctrine\ORM\EntityManagerInterface;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;

/**
 * Klasa OdbieranieUprawnienService
 */
class OdbieranieUprawnienService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * Publiczny konstruktor
     *
     * @param EntityManagerInterface $entityManager
     * @param TokenStorage $tokenStorage
     */
    public function __construct(EntityManagerInterface $entityManager, TokenStorage $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->currentUser = $tokenStorage->getToken()->getUser();
    }

    public function odbierzZasobyUzytkownika(array $dane, int $wniosekId)
    {
      //  $powodOdebrania = $ndata['powod'];

        $zasobyDoZmiany = $this->przygotujDaneDoZmiany($dane);
        $entityManager = $this->entityManager;

        $wniosekNadanieOdebranieZasobow = $entityManager
            ->getRepository(WniosekNadanieOdebranieZasobow::class)
            ->find($wniosekId)
        ;


        foreach ($zasobyDoZmiany as $userZasobId => $zasoby) {
            $modulyDoWyciecia = [];
            $poziomyDostepuDoWyciecia = [];
            foreach ($zasoby as $daneUserZasob) {
                $modulyDoWyciecia[] = $daneUserZasob['modul_zasobu'];
                $poziomyDostepuDoWyciecia[] = $daneUserZasob['poziom_zasobu'];
                $this->klonujUserZasobOdebrania($daneUserZasob, $wniosekNadanieOdebranieZasobow);
            }


            $this->wytnijModulyPoziomyUserZasobu($userZasobId, $modulyDoWyciecia, $poziomyDostepuDoWyciecia);
        }
        $wniosekNadanieOdebranieZasobow->ustawPoleZasoby();

        $entityManager->persist($wniosekNadanieOdebranieZasobow);

       // die;
    }

    /**
     * Z obiektu UserZasob wycina podane moduly oraz poziomy dostępu.
     * Jeżeli zostaną wycięte wszystkie poziomy oraz moduły (czyli nie ma co odbierać)
     * to usuwa obiekt z bazy. ID usuniętego obiektu nadal widnieje jako 'parent'
     * nowo powstałych obiektów UserZasoby do odebrania i będzie można go przywrócić
     * w przypadku odrzucenia wniosku o odebranie tego uprawnienia (zasobu).
     *
     * @param int $userZasobId
     * @param array $moduly
     * @param array $poziomyDostepu
     *
     * @return bool
     */
    private function wytnijModulyPoziomyUserZasobu(int $userZasobId, array $moduly, array $poziomyDostepu): bool
    {
        $userZasob = $this
            ->entityManager
            ->getRepository(UserZasoby::class)
            ->findOneById($userZasobId)
        ;

        $userZasobModuly = explode(';', $userZasob->getModul());
        $userZasobPoziomyDostepu = explode(';', $userZasob->getPoziomDostepu());

        foreach ($userZasobModuly as $key => $userZasobModul) {
            if (in_array($userZasobModul, $moduly)) {
                unset($userZasobModuly[$key]);
            }
        }

        foreach ($userZasobPoziomyDostepu as $key => $userZasobPoziomDostepu) {
            if (in_array($userZasobPoziomDostepu, $poziomyDostepu)) {
                unset($userZasobPoziomyDostepu[$key]);
            }
        }

        $userZasob
            ->setModul(implode(';', $userZasobModuly))
            ->setPoziomDostepu(implode(';', $userZasobPoziomyDostepu))
        ;

        if (!(empty($userZasobModuly) && empty($userZasobPoziomyDostepu))) {
            $this
                ->entityManager
                ->persist($userZasob)
            ;

            return true;
        }

        $this
            ->entityManager
            ->remove($userZasob);

        return false;
    }


    /**
     * Klonuje obiekt UserZasoby do encji.
     * Nowe obiekty zawierają tylko moduł/poziom dostępu który
     * ma być odebrany. Rodzic (klona) jest flagowany.
     *
     * @param array $daneUserZasob
     *
     * @throws \Exception Musi być zasób do odebrania
     *
     * @return void
     */
    private function klonujUserZasobOdebrania(
        array $daneUserZasob,
        WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
    ): void {
        $userZasob = $this
            ->entityManager
            ->getRepository(UserZasoby::class)
            ->findOneById($daneUserZasob['id_zasobu'])
        ;

        if (null === $userZasob) {
            throw new \Exception('Musi być zasób do odebrania..');
        }

        $userZasobDoUsuniecia = clone $userZasob;
        $userZasobDoUsuniecia
            ->setParent($userZasob)
            ->setModul($daneUserZasob['modul_zasobu'])
            ->setPoziomDostepu($daneUserZasob['poziom_zasobu'])
            ->setWniosekOdebranie($wniosekNadanieOdebranieZasobow)
        ;

        $this
            ->entityManager
            ->persist($userZasobDoUsuniecia);

        $userZasob->setIstniejeWniosekOdebranie(true);
        $this
            ->entityManager
            ->persist($userZasob);
}

    /**
     * Wyciąga z tablicy pojedyńcze wpisy nt zasobu i grupuje je.
     *
     * @param array $dane
     *
     * @return array
     */
    private function przygotujDaneDoZmiany(array $dane): array
    {
        $daneDoZmiany = [];

        foreach ($dane as $zasobString) {
            $daneZasobu = $this->parsujTekstUserZasob($zasobString);
            $daneDoZmiany[$daneZasobu['id_zasobu']][] = $daneZasobu;
        }

        return $daneDoZmiany;
    }

    /**
     * Dane wchodzące do tej metody mają postać `42143;Serwer_Treści_Testy;BazaDanych_Administrator`
     * czyli ID obiektu klasy UserZasoby;Moduł Zasobu; Poziom Zasobu;
     *
     * @param string $userZasobString
     *
     * @return array
     */
    private function parsujTekstUserZasob(string $userZasobString): array
    {
        $daneZasobu = explode(';', $userZasobString);
        $idZasobu = $daneZasobu[0];
        $modulZasobu = $daneZasobu[1];
        $poziomZasobu = $daneZasobu[2];

        return [
            'id_zasobu'     => $idZasobu,
            'modul_zasobu'  => $modulZasobu,
            'poziom_zasobu' => $poziomZasobu,
        ];
    }

    /**
     * Cofa zmiany dokonane na obiekcie UserZasoby czyli np. podzielenie modułów
     * i poziomów dostępu na osobne obiekty (powstałe z rozszczepienia stringa
     * oddzielanego średnikiem w metodzie klonujUserZasobOdebrania).
     *
     * @param WniosekNadanieOdebranieZasobow $wniosek
     *
     * @return void
     */
    public function odrzucenieWniosku(WniosekNadanieOdebranieZasobow $wniosek): void
    {
        $userZasoby = $wniosek->getUserZasoby();

        $entityManager = $this->entityManager;

        $zmianyModulPoziom = [];
        foreach ($userZasoby as $userZasob) {
            if (null === $userZasob->getParent()) {
                throw new \Exception('Ten sposób odrzucenia wniosku nie jest wstecznie kompatybilny.');
            }
            $parentId = $userZasob->getParent()->getId();
            $zmianyModulPoziom[$parentId]['moduly'][] = (string) $userZasob->getModul();
            $zmianyModulPoziom[$parentId]['poziomy'][] = (string) $userZasob->getPoziomDostepu();
            $entityManager->remove($userZasob);
        }

        foreach ($zmianyModulPoziom as $userZasobId => $modulyPoziomy) {
            $userZasob = $entityManager
                ->getRepository(UserZasoby::class)
                ->findOneById($userZasobId)
            ;

            $userZasobModuly = explode(';', $userZasob->getModul());
            $userZasobPoziomDostepu = explode(';',  $userZasob->getPoziomDostepu());

            foreach ($modulyPoziomy['moduly'] as $modul) {
                if (!in_array($modul, $userZasobModuly)) {
                    $userZasobModuly[] = $modul;
                }
            }

            foreach ($modulyPoziomy['poziomy'] as $poziom) {
                if (!in_array($poziom, $userZasobPoziomDostepu)) {
                    $userZasobPoziomDostepu[] = $poziom;
                }
            }

            $userZasob
                ->setModul(implode(';', $userZasobModuly))
                ->setPoziomDostepu(implode(';', $userZasobPoziomDostepu))
            ;

            $entityManager->persist($userZasob);
        }
    }
}
