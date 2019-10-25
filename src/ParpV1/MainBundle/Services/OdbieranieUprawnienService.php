<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;
use Doctrine\ORM\EntityManagerInterface;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Services\ZasobyService;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Helper\IloczynKartezjanskiHelper;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\Form;
use ParpV1\MainBundle\Form\EdycjaUzytkownikaFormType;
use ParpV1\MainBundle\Constants\WyzwalaczeConstants;
use ReflectionClass;
use InvalidArgumentException;
use ParpV1\MainBundle\Entity\OdebranieZasobowEntry;

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
     * @var ZasobyService
     */
    private $zasobyService;

    /**
     * @var UprawnieniaService
     */
    private $uprawnieniaService;

    /**
     * @var string
     */
    const ZASOB_USUNIETY_POWOD = 'Zasób usunięty, odebrano uprawnienia.';

    /**
     * Publiczny konstruktor
     *
     * @param EntityManagerInterface $entityManager
     * @param TokenStorage $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorage $tokenStorage,
        ZasobyService $zasobyService,
        UprawnieniaService $uprawnieniaService
    ) {
        $this->zasobyService = $zasobyService;
        $this->entityManager = $entityManager;
        $this->currentUser = $tokenStorage->getToken()->getUser();
        $this->uprawnieniaService = $uprawnieniaService;
    }

    /**
     * Odbiera uprawnienia do zasobu wszytkim jego użytkownikom.
     *
     * @param Zasoby $zasob
     * @param DateTime $dataOdebrania
     *
     * @return void
     */
    public function wyzerujUzytkownikowZasobu(Zasoby $zasob, DateTime $dataOdebrania): void
    {
        $entityManager = $this->entityManager;
        $userZasoby = $entityManager
            ->getRepository(UserZasoby::class)
            ->findBy([
                'zasobId' => $zasob->getId(),
            ]);

        if (!empty($userZasoby)) {
            foreach ($userZasoby as $userZasob) {
                $this->ustawJakoOdbierany($userZasob, null, self::ZASOB_USUNIETY_POWOD, $dataOdebrania);
            }
        }
    }

    /**
     * Rozpoczyna proces odbierania uprawnień.
     *
     * @param array $dane
     * @param int $wniosekId
     * @param string $powodOdebrania
     *
     * @return bool fałsz jeżeli jest to uproszczone odbieranie uprawnień
     */
    public function odbierzZasobyUzytkownika(array $dane, int $wniosekId, string $powodOdebrania): bool
    {
        $zasobyDoZmiany = $this->przygotujDaneDoZmiany($dane);
        $entityManager = $this->entityManager;

        $wniosekNadanieOdebranieZasobow = $entityManager
            ->getRepository(WniosekNadanieOdebranieZasobow::class)
            ->find($wniosekId)
        ;

        $podzieloneUserZasoby = [];
        foreach ($zasobyDoZmiany as $userZasobId => $userZasoby) {
            if (!isset($podzieloneUserZasoby[$userZasobId])) {
                $podzieloneUserZasoby[$userZasobId] = $this->podzielZasob($userZasobId);
            }

            foreach ($userZasoby as $userZasob) {
                if ('BRAK' === $userZasob['poziom_zasobu']) {
                    $userZasob['poziom_zasobu'] = '';
                }
                $criteria = Criteria::create();
                $criteria
                    ->where(
                        Criteria::expr()
                            ->eq('poziomDostepu', $userZasob['poziom_zasobu'])
                    )
                    ->andWhere(
                        Criteria::expr()
                            ->eq('modul', $userZasob['modul_zasobu'])
                    )
                ;

                $userZasobDoWniosku = $podzieloneUserZasoby[$userZasobId]->matching($criteria);
                $userZasobDoWniosku = $userZasobDoWniosku->current();

                $this->ustawJakoOdbierany($userZasobDoWniosku, $wniosekNadanieOdebranieZasobow, $powodOdebrania);
                if (null !== $wniosekNadanieOdebranieZasobow) {
                    $wniosekNadanieOdebranieZasobow
                        ->setZawieraZasobyZAd($this->zasobyService->czyZasobMaGrupyAd($userZasobDoWniosku))
                    ;
                }
            }
        }

        if (null !== $wniosekNadanieOdebranieZasobow) {
            $wniosekNadanieOdebranieZasobow->ustawPoleZasoby();
            $entityManager->persist($wniosekNadanieOdebranieZasobow);

            return true;
        }

        return false;
    }

    /**
     * Oznacza obiekt UserZasob jako odbierany.
     *
     * @param UserZasoby $userZasob
     * @param WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
     * @param string $powodOdebrania
     * @param DateTime $dataOdebrania
     *
     * @return void
     */
    private function ustawJakoOdbierany(
        UserZasoby $userZasob,
        WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow = null,
        string $powodOdebrania,
        DateTime $dataOdebrania = null
    ): void {
        $userZasob
            ->setWniosekOdebranie($wniosekNadanieOdebranieZasobow)
            ->setPowodOdebrania($powodOdebrania)
        ;

        if (null === $wniosekNadanieOdebranieZasobow) {
            $userZasob
                ->setCzyOdebrane(true)
                ->setKtoOdebral($this->currentUser)
                ->setCzyAktywne(false)
                ->setDataOdebrania(null === $dataOdebrania ? new DateTime() : $dataOdebrania)
            ;
        }

        if (null !== $wniosekNadanieOdebranieZasobow) {
            $this->dodajKomentarzOdebrania($wniosekNadanieOdebranieZasobow, $userZasob);
        }

        $this
            ->entityManager
            ->persist($userZasob);
    }

    /**
     * Dzieli Obiekt UserZasoby na oddzielne (moduły i poziomy dostępu oddzielnie).
     *
     * @param int $userZasobId
     *
     * @return ArrayCollection
     */
    private function podzielZasob(int $userZasobId): ArrayCollection
    {
        $userZasob = $this
            ->entityManager
            ->getRepository(UserZasoby::class)
            ->findOneById($userZasobId)
        ;

        if (null === $userZasob) {
            throw new \Exception('Musi być zasób do odebrania..');
        }

        $userZasobModuly = explode(';', $userZasob->getModul());
        $userZasobPoziomDostepu = explode(';', $userZasob->getPoziomDostepu());

        $podzielonyZasob = IloczynKartezjanskiHelper::build([$userZasobModuly, $userZasobPoziomDostepu]);

        $utworzoneUserZasoby = new ArrayCollection();

        if (1 === count($podzielonyZasob)) {
            $utworzoneUserZasoby
                ->add($userZasob)
            ;

            return $utworzoneUserZasoby;
        }

        foreach ($podzielonyZasob as $modulPoziom) {
            $nowyUserZasob = clone $userZasob;
            $nowyUserZasob
                ->setModul($modulPoziom[0])
                ->setPoziomDostepu($modulPoziom[1])
            ;

            $this
                ->entityManager
                ->persist($nowyUserZasob)
            ;

            $utworzoneUserZasoby->add($nowyUserZasob);
        }

        $this
            ->entityManager
            ->remove($userZasob)
        ;

        return $utworzoneUserZasoby;
    }

    /**
     * Odnotowuje we wniosku które zasoby mają być odebrane (ich moduły oraz poziomy).
     * W przypadku odrzucenia wniosku obiekty te zostaną usunięte z zakładki `Zasoby`
     * dlatego pozostawiony komentarz będzie informacją co zawierał wniosek.
     *
     * @param WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
     * @param UserZasoby $userZasob
     *
     * @return void
     */
    private function dodajKomentarzOdebrania(
        WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow,
        UserZasoby $userZasob
    ): void {
        $entityManager = $this->entityManager;
        $zasob = $entityManager
            ->getRepository(Zasoby::class)
            ->findOneById($userZasob->getZasobId())
        ;
        $opisKomentarza = 'Zasób: ' . $zasob->getNazwa() .
            '<br/>Moduł: ' . $userZasob->getModul() .
            '<br/>Poziom dostepu: ' . $userZasob->getPoziomDostepu() .
            '<br/>Powód odebrania: ' . $userZasob->getPowodOdebrania()
        ;

        $komentarz = new Komentarz();
        $komentarz
            ->setSamaccountname($this->getCurrentUser())
            ->setTytul('Odbierany zasób')
            ->setOpis($opisKomentarza)
            ->setObiekt('WniosekNadanieOdebranieZasobow')
            ->setObiektId($wniosekNadanieOdebranieZasobow->getId())
        ;

        $entityManager
            ->persist($komentarz)
        ;
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
     * Odnotowuje na zasobie, że nie jest już odbierany.
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
            $userZasob
                ->setWniosekOdebranie(null)
                ->setPowodOdebrania(null)
            ;

            $entityManager->persist($userZasob);
        }
    }

    /**
     * Zwraca zalogowanego użytkownika z TokenStorage.
     *
     * @return ParpUser
     */
    private function getCurrentUser(): ParpUser
    {
        return $this->currentUser;
    }

    /**
     * Okresla zmianę na koncie i tworzy obiekt OdebranieZasobowEntry
     * używany później (przy wypychaniu zmian do AD) do wyzerowania zasobów użytkownika.
     *
     * @param array $oldData
     * @param Form $form
     *
     * @return null|OdebranieZasobowEntry
     */
    public function utworzOdebranieZasobowEntry(array $oldData, Form $form)
    {
        $formData = $form->getData();
        $wyzwalacz = $this->okreslZmianeUzytkownika($oldData, $formData);

        $accountName = $formData['samaccountname'];
        $nieCzyszczoneWyzwalacze = [
            // nie wywalam tego całkowicie - zostawiam na przysżłość, bo jeszcze może się coś zmienić.
            // WyzwalaczeConstants::DLUGOTRWALA_NIEOBECNOSC,
        ];

        if ($wyzwalacz && !in_array($wyzwalacz, $nieCzyszczoneWyzwalacze)) {
            $powodOdebrania = $this->getPowodOdebraniaWyzwalacz($wyzwalacz);

            $odebranieZasobowEntry = new OdebranieZasobowEntry();
            $odebranieZasobowEntry
                ->setUzytkownik($accountName)
                ->setPowodOdebrania($powodOdebrania)
            ;

            $this
                ->entityManager
                ->persist($odebranieZasobowEntry)
            ;

            return $odebranieZasobowEntry;
        }

        return null;
    }

    /**
     * Pobiera ze stałej odpowiedni powód wpisywany do komentarza w trakcie odebrania uprawnienia.
     *
     * @param string $trigger
     *
     * @return string
     */
    private function getPowodOdebraniaWyzwalacz(string $trigger): string
    {
        $wyzwalaczeConstants = new ReflectionClass(WyzwalaczeConstants::class);
        $triggerConstantsValues = $wyzwalaczeConstants
            ->getConstants()
        ;

        $constantKey = null;
        foreach ($triggerConstantsValues as $key => $value) {
            if ($trigger === $value) {
                $constantKey = $key;
            }
        }

        if (null === $constantKey) {
            throw new InvalidArgumentException('Nie odnaleziono podanego wyzwalacza.');
        }

        return $wyzwalaczeConstants->getConstant($constantKey . WyzwalaczeConstants::TITLE_SEPARATOR);
    }

    /**
     * Określa które pole w formularzu edycji użytkownika zostało zmienione.
     * Zwraca wyzwalacz który powoduje reset uprawnień początkowych.
     * Porównywane są dane wchodzące i wychodzące z formularza klasy EdycjaUzytkownikaFormType.
     *
     * @todo ZMIANA_STANOWISKA pewnie musi byc okreslone jakieś stanowisko
     *
     * @param array $oldData
     * @param array $formData
     *
     * @return string
     */
    private function okreslZmianeUzytkownika(array $oldData, array $formData): string
    {
        $wyzwalacz = WyzwalaczeConstants::RESET_DO_UPRAWNIEN_POCZATKOWYCH;

        $kontoWylaczone = $formData['isDisabled'];
        if ($kontoWylaczone) {
            $powodWylaczenia = $formData['disableDescription'];

            if (EdycjaUzytkownikaFormType::WYLACZENIE_KONTA_NIEOBECNOSC === $powodWylaczenia) {
                $wyzwalacz = WyzwalaczeConstants::DLUGOTRWALA_NIEOBECNOSC;
            }

            if (EdycjaUzytkownikaFormType::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY === $powodWylaczenia) {
                $wyzwalacz = WyzwalaczeConstants::ROZWIAZANIE_UMOWY_O_PRACE;
            }
        }

        $stareStanowisko = $oldData['title'];
        $noweStanowisko = $formData['title'];
        if ($stareStanowisko !== $noweStanowisko) {
            $stanowiskaZmiany = [
                'kierownik',
                'ekspert',
                'zastępca dyrektora',
                'dyrektor',
                'główny księgowy, dyrektor',
                'zastępca prezesa',
                'prezes'
            ];
            if (in_array($noweStanowisko, $stanowiskaZmiany)) {
                $wyzwalacz = WyzwalaczeConstants::ZMIANA_STANOWISKA;
            }
        }

        $staraSekcja = $oldData['info'];
        $nowaSekcja = $formData['info'];
        if ($staraSekcja !== $nowaSekcja) {
            $wyzwalacz = WyzwalaczeConstants::ZMIANA_SEKCJI;
        }

        $staryDepartament = $oldData['department'];
        $nowyDepartament = $formData['department'];
        if ($staryDepartament !== $nowyDepartament) {
            $wyzwalacz = WyzwalaczeConstants::ZMIANA_DEPARTAMENTU;
        }

        return $wyzwalacz;
    }
}
