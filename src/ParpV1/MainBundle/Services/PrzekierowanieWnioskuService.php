<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Constants\TypWnioskuConstants;
use ParpV1\MainBundle\Entity\Wniosek;
use Symfony\Component\Form\Form;
use ParpV1\MainBundle\Entity\Komentarz;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use InvalidArgumentException;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\WniosekStatus;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use ParpV1\MainBundle\Entity\WniosekViewer;
use ParpV1\MainBundle\Entity\WniosekEditor;

class PrzekierowanieWnioskuService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var StatusWnioskuService
     */
    private $statusWnioskuService;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var Wniosek
     */
    private $wniosek = null;

    /**
     * @var bool
     */
    private $doFlush = false;

    /**
     * @var string
     */
    const TYTUL_KOMENTARZA = 'Ręczne przekierowanie wniosku';

    /**
     * @var string
     */
    const ZMIANA_STATUS = 'zmiana_statusu';

    /**
     * @var string
     */
    const ZMIANA_OSOBA = 'zmiana_osob';

    /**
     * @var string
     */
    const BRAK_ZMIAN = 'brak_zmian';

    /**
     * Publiczny konstruktor
     *
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param StatusWnioskuService $statusWnioskuService
     * @param Session $session
     */
    public function __construct(
        EntityManager $entityManager,
        UserService $userService,
        StatusWnioskuService $statusWnioskuService,
        Session $session
    ) {
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->statusWnioskuService = $statusWnioskuService;
        $this->flashBag = $session->getFlashBag();
    }

    /**
     * Rozpoczyna proces przekierowania wniosku.
     *
     * @param Form $form
     *
     * @throws EntityNotFoundException gdy nie odnaleziono statusu.
     *
     * @return void
     */
    public function przekierujWniosekForm(Form $form)
    {
        $formData = $form->getData();
        $zakresZmian = $this->okreslZakresZmian($formData);

        if (self::BRAK_ZMIAN === $zakresZmian) {
            $this->addFlashMessage('danger', 'Nie przekierowano wniosku - nie znaleziono zmian.');

            return $this;
        }

        $entityManager = $this->entityManager;
        $trescKomentarza = '';
        $statusWniosku = $this->wniosek->getStatus();
        if ($statusWniosku->getId() !== $formData['status'] && !empty($formData['status'])) {
            if (null !== $formData['status']){
                $nowyStatus = $entityManager
                    ->getRepository(WniosekStatus::class)
                    ->find($formData['status']);

                if (null === $nowyStatus) {
                    throw new EntityNotFoundException('Nie odnaleziono statusu.');
                }
                $statusWnioskuService = $this->statusWnioskuService;
                $statusWnioskuService->setWniosekStatus(
                    $this->getDzieckoWniosku(),
                    $nowyStatus->getNazwaSystemowa(),
                    false,
                    null
                );

                $trescKomentarza = '<br/><b>Zmiany: </b><br/>Z: ' . $statusWniosku->getNazwaSystemowa() .
                '<br/>NA: ' . $nowyStatus->getNazwaSystemowa();

                $this->addFlashMessage('warning', 'Zmieniono ręcznie status wniosku. ');
            }
        } elseif (empty($formData['status'])) {
            $trescKomentarza = '<br/><b>Zmiany: </b><br/>Z (editors): ' . $this->wniosek->getEditornames() .
            '<br/>NA (editors): ' . implode(', ', $formData['editors']) .
            '<br/>Z (viewers): ' . $this->wniosek->getViewernames() .
            '<br/>NA (viewers): ' . implode(', ', $formData['viewers']);

            $this->zmienEditorsViewers($formData);
            $this->addFlashMessage('warning', 'Zmieniono ręcznie osoby widzące i mogące edytować wniosek. ');
        }

        $this->dodajKomentarzPrzekierowania($formData, $trescKomentarza);

        if ($this->doFlush) {
            if ($this->wniosek->getWniosekUtworzenieZasobu()) {
                $this->wniosek->setWniosekNadanieOdebranieZasobow(null);
            }
            $entityManager->flush();
        }
    }

    /**
     * Zmienia editors i viewers wniosku na podstawie danych z formularza.
     *
     * @param array $formData
     * @param Wniosek|null $wniosek
     *
     * @return void
     */
    private function zmienEditorsViewers(array $formData, Wniosek $wniosek = null): void
    {
        if (null === $wniosek) {
            if (null === $this->wniosek) {
                throw new UnexpectedTypeException('null', Wniosek::class);
            }

            $wniosek = $this->wniosek;
        }

        $entityManager = $this->entityManager;
        foreach ($wniosek->getViewers() as $viewer) {
            $entityManager->remove($viewer);
        }
        foreach ($wniosek->getEditors() as $editor) {
            $entityManager->remove($editor);
        }

        foreach ($formData['viewers'] as $nowyViewer) {
            $wniosekViewer = new WniosekViewer();
            $wniosekViewer
                ->setSamaccountname($nowyViewer)
                ->setWniosek($wniosek)
            ;
            $wniosek->addViewer($wniosekViewer);
            $entityManager->persist($wniosekViewer);
        }

        foreach ($formData['editors'] as $nowyEditor) {
            $wniosekEditor = new WniosekEditor();
            $wniosekEditor
                ->setSamaccountname($nowyEditor)
                ->setWniosek($wniosek)
            ;
            $wniosek->addEditor($wniosekEditor);
            $entityManager->persist($wniosekEditor);
        }

        $wniosek
            ->setEditornamesSet()
            ->setViewernamesSet();
    }

    /**
     * Add flash message
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    private function addFlashMessage($type = 'success', $message = self::TYTUL_KOMENTARZA): void
    {
        $this
            ->flashBag
            ->add($type, $message)
        ;
    }

    /**
     * Porównuje zapisane zmiany we wniosku z danymi wprowadzonymi w formularzu.
     * Po stronie JS jest możliwość wybrania pól do uzupelnienia dlatego jest tutaj
     * walidacja czy nie zostały uzupełnione pola wykluczające się.
     *
     * @param array $formData
     * @param Wniosek|null $wniosek
     *
     * @return string
     */
    private function okreslZakresZmian(array $formData, Wniosek $wniosek = null): string
    {
        if (null === $wniosek) {
            if (null === $this->wniosek) {
                throw new UnexpectedTypeException('null', Wniosek::class);
            }

            $wniosek = $this->wniosek;
        }

        $zmianaEditorsViewers = function () use ($formData, $wniosek) {
            $zmiana = false;

            $formDataEditors = array_values($formData['editors']);
            $wniosekEdtors = explode(',', $wniosek->getEditornames());
            asort($formDataEditors);
            asort($wniosekEdtors);

            if ($formDataEditors !== $wniosekEdtors) {
                $zmiana = true;
            }

            $formDataViewers = array_values($formData['viewers']);
            $wniosekViewers = explode(',', $wniosek->getViewernames());
            asort($formDataViewers);
            asort($wniosekViewers);

            if ($formDataViewers !== $wniosekViewers) {
                $zmiana = true;
            }

            if (empty($formDataEditors) && empty($formDataViewers)) {
                $zmiana = false;
            }

            return $zmiana;
        };

        $zmianaStatus = function () use ($formData, $wniosek) {
            if (empty($formData['status'])) {
                return false;
            }

            return $formData['status'] !== $wniosek->getStatus()->getId();
        };

        $zmianaStatus = $zmianaStatus();
        $zmianaEditorsViewers = $zmianaEditorsViewers();

        if ($zmianaStatus && $zmianaEditorsViewers) {
            throw new InvalidArgumentException('Nie można zmienić statusu i osób jednocześnie.');
        }

        if ($zmianaStatus) {
            return self::ZMIANA_STATUS;
        }

        if ($zmianaEditorsViewers) {
            return self::ZMIANA_OSOBA;
        }

        return self::BRAK_ZMIAN;
    }

    /**
     * Dodaje komentarz o przekierowaniu do wniosku.
     *
     * @param array $formData
     * @param string $listaZmian
     *
     * @return void
     */
    private function dodajKomentarzPrzekierowania(array $formData, $listaZmian = ''): void
    {
        $komentarz = new Komentarz();
        $dzieckoWniosku = $this->getDzieckoWniosku();
        $komentarz
            ->setSamaccountname($this->userService->getCurrentUser())
            ->setTytul(self::TYTUL_KOMENTARZA)
            ->setOpis($formData['powod'] . $listaZmian)
            ->setObiekt($this->okreslTypWniosku())
            ->setObiektId($dzieckoWniosku->getId())
        ;

        $this
            ->entityManager
            ->persist($komentarz)
        ;
    }

    /**
     * Zwraca obiekt dziecka wniosku na podstawie jego typu.
     *
     * @param Wniosek|null $wniosek
     *
     * @throws InvalidArgumentException gdy typ wniosku jest niepoprawny.
     *
     * @return object
     */
    public function getDzieckoWniosku(Wniosek $wniosek = null): object
    {
        $typWniosku = $this->okreslTypWniosku();
        if (null === $wniosek) {
            if (null === $this->wniosek) {
                throw new UnexpectedTypeException('null', Wniosek::class);
            }

            $wniosek = $this->wniosek;
        }

        if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
            return $wniosek
                ->getWniosekUtworzenieZasobu()
            ;
        }

        if (TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW === $typWniosku) {
            return $wniosek
                ->getWniosekNadanieOdebranieZasobow()
            ;
        }

        throw new InvalidArgumentException('Niepoprawny typ wniosku.');
    }

    /**
     * Określa typ wniosku.
     *
     * @param Wniosek|null
     *
     * @throws UnexpectedTypeException jeżeli wniosek nie jest ustawiony
     *
     * @return string
     */
    private function okreslTypWniosku(Wniosek $wniosek = null): string
    {
        if (null === $wniosek) {
            if (null === $this->wniosek) {
                throw new UnexpectedTypeException('null', Wniosek::class);
            }

            $wniosek = $this->wniosek;
        }

        $typWniosku = $wniosek->getWniosekNadanieOdebranieZasobow() ?
            TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW :
            TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU;

        return $typWniosku;
    }

    /**
     * Set wniosek
     *
     * @param Wniosek
     *
     * @return PrzekierowanieWnioskuService
     */
    public function setWniosek(Wniosek $wniosek): PrzekierowanieWnioskuService
    {
        $this->wniosek = $wniosek;

        return $this;
    }

    /**
     * Będzie przeprowadzony flush na koniec.
     *
     * @return PrzekierowanieWnioskuService
     */
    public function doFlush(): PrzekierowanieWnioskuService
    {
        $this->doFlush = true;

        return $this;
    }
}
