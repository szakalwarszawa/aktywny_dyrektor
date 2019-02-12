<?php
namespace ParpV1\MainBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use ParpV1\MainBundle\Entity\Zasoby;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Entity\Zastepstwo;
use ParpV1\AuthBundle\Security\ParpUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use DateTime;

/**
 * Klasa WnioskiNadanieOdebranieGrid
 */
class WnioskiNadanieOdebranieGrid
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var string
     */
    private $typWniosku = null;

    /**
     * @var bool
     */
    private $pokazKiedyBrakDanych = false;

    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * @var bool
     */
    private $ajaxGrid;

    /**
     * Publiczny konstruktor.
     *
     * @param Grid              $grid
     * @param EntityManager     $entityManager
     * @param TokenStorage      $tokenStorage
     */
    public function __construct(Grid $grid, EntityManager $entityManager, TokenStorage $tokenStorage, bool $ajaxGrid)
    {
        $this->entityManager = $entityManager;
        $this->grid = $grid;
        $this->currentUser = $tokenStorage->getToken()->getUser();
        $this->ajaxGrid = $ajaxGrid;
    }

    /**
     * Ustawia typ wniosku który będzie wyszukiwany.
     * Na podstawie dozwolonych typóœ określonych w tablicy.
     *
     * @param string $typWniosku
     *
     * @return WnioskiNadanieOdebranieGrid
     *
     * @throws InvalidArgumentException gdy typ wniosku niedozwolony/niepoprawny
     */
    public function setTypWniosku(string $typWniosku): self
    {
        $dozwoloneTypyWnioskow = [
            WniosekNadanieOdebranieZasobow::WNIOSKI_OCZEKUJACE,
            WniosekNadanieOdebranieZasobow::WNIOSKI_WSZYSTKIE,
            WniosekNadanieOdebranieZasobow::WNIOSKI_W_TOKU,
            WniosekNadanieOdebranieZasobow::WNIOSKI_ZAKONCZONE
        ];

        if (in_array($typWniosku, $dozwoloneTypyWnioskow)) {
            $this->typWniosku = $typWniosku;

            return $this;
        }

        throw new InvalidArgumentException('Nieporawny typ wniosku.');
    }

    /**
     * Grid zostanie wyświetlony nawet jeżeli nie ma danych.
     *
     * @return WnioskiNadanieOdebranieGrid
     */
    public function forceWyswietlGrid(): self
    {
        $this
            ->grid
            ->setNoDataMessage(false)
        ;

        return $this;
    }

    /**
     * Generuje siatkę z danymi.
     * Pusty grid jest generowany np. w zakładce `wszystkie`.
     *
     * @param bool $pustyGrid
     *
     * @return Grid
     */
    public function generateGrid(bool $pustyGrid = false): Grid
    {
        if (null === $this->typWniosku) {
            throw new InvalidArgumentException('Typ wniosku musi być zdefiniowany przed generowaniem siatki.');
        }

        $daneGrid = [];
        if (!$pustyGrid) {
            $daneGrid = $this->getData();
        }

        $kolumnyGrid = $this->getColumns();

        $vector = new Vector($daneGrid, $kolumnyGrid);
        $grid = $this->grid;
        $grid->setSource($vector);

        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        $grid->getColumn('pracownicy')->manipulateRenderCell(
            function ($value, $row, $router) {
                    return str_replace(array(";", ","), ', ', $value);
            }
        );

        $grid
            ->getColumn('akcje')
            ->setFilterable(false)
            ->setSafe(true);

        $grid = $this->dodajAkcjeGrid($grid);

        if (WniosekNadanieOdebranieZasobow::WNIOSKI_WSZYSTKIE === $this->typWniosku && !$this->ajaxGrid) {
            $dataGraniczna = new DateTime('-14 days');
            $grid->setDefaultFilters([
                'utworzonyDnia' => [
                    'operator' => 'gte',
                    'from' => $dataGraniczna->format('Y-m-d')
                ]
            ]);
        }

        return $grid;
    }

    /**
     * Dodaje przyciski akcji do siatki.
     *
     * @param Grid $grid
     *
     * @return Grid
     */
    private function dodajAkcjeGrid(Grid $grid): Grid
    {
        $rowAction = new RowAction(
            ' Edycja',
            'wnioseknadanieodebraniezasobow_edit'
        );
        $rowAction
            ->setColumn('akcje')
            ->addAttribute('class', 'btn btn-success btn-xs glyphicon glyphicon-pencil')
        ;
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            ' Pokaż',
            'wnioseknadanieodebraniezasobow_show'
        );
        $rowAction
            ->setColumn('akcje')
            ->addAttribute('class', 'btn btn-info btn-xs');
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            ' Skasuj',
            'wnioseknadanieodebraniezasobow_delete_form'
        );
        $rowAction
            ->setColumn('akcje')
            ->addAttribute('class', 'btn btn-danger btn-xs fa fa-delete')
            ->addManipulateRender(
                function ($action, $row) {
                    if ($row->getField('wniosek.numer') == 'wniosek w trakcie tworzenia') {
                        return $action;
                    } else {
                        return null;
                    }
                }
            );
        $grid->addRowAction($rowAction);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));

        return $grid;
    }

    /**
     * Zwraca dane do grida.
     *
     * @return array
     */
    private function getData(): array
    {
        $entityManager = $this->entityManager;

        $zastepstwa = $entityManager
            ->getRepository(Zastepstwo::class)
            ->znajdzKogoZastepuje($this->getCurrentUser())
        ;

        $listaWnioskow = $entityManager
            ->getRepository(WniosekNadanieOdebranieZasobow::class)
            ->findWnioskiDoZakladki($this->typWniosku, $zastepstwa)
        ;

        return $listaWnioskow;
    }

    /**
     * Zwraca kolumny do grida
     *
     * @return array
     */
    private function getColumns(): array
    {
        return [
            new Column\NumberColumn([
                'id'      => 'id',
                'field'   => 'id',
                'source'  => true,
                'primary' => true,
                'visible' => false,
            ]),
            new Column\TextColumn([
                'id'      => 'numerWniosku',
                'field'   => 'numerWniosku',
                'source'  => true,
                'title'   => 'Numer wniosku',
            ]),
            new Column\TextColumn([
                'id'      => 'statusWniosku',
                'field'   => 'statusWniosku',
                'source'  => true,
                'title'   => 'Status wniosku',
            ]),
            new Column\TextColumn([
                'id'      => 'utworzonyPrzez',
                'field'   => 'utworzonyPrzez',
                'source'  => true,
                'title'   => 'Utworzony przez',
            ]),
            new Column\DateColumn([
                'id'      => 'utworzonyDnia',
                'field'   => 'utworzonyDnia',
                'source'  => true,
                'title'   => 'Utworzony dnia',
                'format' => 'd/m/Y',
            ]),
            new Column\TextColumn([
                'id'      => 'zablokowanyPrzez',
                'field'   => 'zablokowanyPrzez',
                'source'  => true,
                'title'   => 'Zablokowany przez',
            ]),
            new Column\TextColumn([
                'id'      => 'pracownicy',
                'field'   => 'pracownicy',
                'source'  => true,
                'title'   => 'Pracownicy',
            ]),
            new Column\TextColumn([
                'id'      => 'edytorzy',
                'field'   => 'edytorzy',
                'source'  => true,
                'title'   => 'Edytorzy',
            ]),
            new Column\TextColumn([
                'id'      => 'zasoby',
                'field'   => 'zasoby',
                'source'  => true,
                'title'   => 'Zasoby',
            ]),
        ];
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
}
