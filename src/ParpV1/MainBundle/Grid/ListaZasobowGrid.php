<?php
namespace ParpV1\MainBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use ParpV1\MainBundle\Entity\Zasoby;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Export\ExcelExport;

class ListaZasobowGrid
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Publiczny konstruktor.
     *
     * @param Grid          $grid
     * @param EntityManager $entityManager
     * @param array         $parameters
     */
    public function __construct(Grid $grid, $entityManager, array $parameters = array())
    {
        $this->entityManager = $entityManager;
        $this->grid = $grid;
        $this->parameters = $parameters;
    }

    public function generate()
    {
        $zasoby = $this->getData($this->parameters['aktywne']);
        $kolumny = $this->getColumns();

        $zrodloDanych = new Vector($zasoby, $kolumny);

        $siatka = $this->grid;
        $siatka->setSource($zrodloDanych);

        $actionsColumn = new ActionsColumn('akcja', 'Działania');
        $actionsColumn->setSize(150);
        $siatka->addColumn($actionsColumn);

        $siatka->getColumn('akcja')
                ->setFilterable(false)
                ->setSafe(true);

        $siatka->setVisibleColumns(array(
            'id',
            'nazwa',
            'opisZasobu',
            'wlascicielZasobu',
            'powiernicyWlascicielaZasobu',
            'administratorZasobu',
            'administratorTechnicznyZasobu',
            'Numer',
            'akcja'
        ));

        $zasobAkcja = new RowAction(
            '<i class="glyphicon glyphicon-pencil"></i> Edycja',
            'zasoby_edit',
            null,
            null,
            array(
                'class' => 'btn btn-success btn-xs',
            )
        );
        $zasobAkcja->setColumn('akcja');
        $siatka->addRowAction($zasobAkcja);

        if ($this->parameters['aktywne']) {
            $zasobAkcja = new RowAction(
                '<i class="fa fa-ban"></i> ' .
                'Dezaktywuj',
                'zasoby_delete',
                null,
                null,
                array(
                    'class' => 'btn btn-danger btn-xs',
                )
            );
            $zasobAkcja->setColumn('akcja');
            $zasobAkcja->addManipulateRender(
                function ($action, $row) {
                    return $this->adminRowAction($action, $row);
                }
            );
            $siatka->addRowAction($zasobAkcja);
        } else {
            $zasobAkcja = new RowAction(
                '<i class="fa fa-check"></i> ' .
                'Aktywuj',
                'zasoby_aktywuj',
                null,
                null,
                array(
                    'class' => 'btn btn-warning btn-xs',
                )
            );
            $zasobAkcja->setColumn('akcja');
            $zasobAkcja->addManipulateRender(
                function ($action, $row) {
                    return $this->adminRowAction($action, $row);
                }
            );
            $siatka->addRowAction($zasobAkcja);
        }



        $siatka->isReadyForRedirect();

        return $siatka;
    }

    /**
     * Sprawdza czy użytkownik ma odpowiednią rolę aby zobaczyć
     * akcję wiersza dostępną dla wybranych ról.
     *
     * @param RowAction $acton
     * @param Row $row
     *
     * @return null|RowAction
     */
    private function adminRowAction($action, $row)
    {
        $roleDozwolone = array(
            'PARP_ADMIN',
            'PARP_ADMIN_REJESTRU_ZASOBOW',
        );
        if (in_array('PARP_ADMIN', $this->parameters['uzytkownik']->getRoles())
            || in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $this->parameters['uzytkownik']->getRoles())) {
            return $action;
        }

        return null;
    }

    private function getData($aktywne)
    {
        $zasoby = $this
            ->entityManager
            ->getRepository(Zasoby::class)
            ->findListaZasobow($aktywne);
        $zasobyService = $this->parameters['zasoby_service'];
        $zasobyPrzefiltrowane = array();
        foreach ($zasoby as $zasob) {
            if (true === $zasob['zasobSpecjalny']) {
                if (true === $zasobyService->zasobSpecjalnyDostep($zasob, $this->parameters['uzytkownik'])) {
                    $zasobyPrzefiltrowane[] = $zasob;
                }
            } elseif (true !== $zasob['zasobSpecjalny']) {
                $zasobyPrzefiltrowane[] = $zasob;
            }
        }

        return $zasobyPrzefiltrowane;
    }

    private function getColumns()
    {
        $columns = array(
            new Column\NumberColumn(array(
                'id'      => 'id',
                'field'   => 'id',
                'source'  => true,
                'primary' => true,
                'title'   => 'ID',
                'size' => 80,
            )),
            new Column\TextColumn(array(
                'id'      => 'nazwa',
                'field'   => 'nazwa',
                'source'  => true,
                'title'   => 'Nazwa'
            )),
            new Column\TextColumn(array(
                'id'      => 'wlascicielZasobu',
                'field'   => 'wlascicielZasobu',
                'source'  => true,
                'title'   => 'Właściciel zasobu',
                'size'    => 200,
            )),
            new Column\TextColumn(array(
                'id'      => 'powiernicyWlascicielaZasobu',
                'field'   => 'powiernicyWlascicielaZasobu',
                'source'  => true,
                'title'   => 'Powiernicy właściciela zasobu',
                'size'    => 200,
            )),
            new Column\TextColumn(array(
                'id'      => 'administratorZasobu',
                'field'   => 'administratorZasobu',
                'source'  => true,
                'title'   => 'Administrator zasobu'
            )),
            new Column\TextColumn(array(
                'id'      => 'administratorTechnicznyZasobu',
                'field'   => 'administratorTechnicznyZasobu',
                'source'  => true,
                'title'   => 'Administrator techniczny zasobu'
            )),
            new Column\TextColumn(array(
                'id'      => 'opisZasobu',
                'field'   => 'opisZasobu',
                'source'  => true,
                'title'   => 'Opis zasobu',
                'size'    => 500,
            )),
        );

        return $columns;
    }
}
