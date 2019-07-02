<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use ParpV1\AuthBundle\Security\ParpUser;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Action\RowAction;

/**
 * Siatka PathsGrid
 */
class PathsGrid
{
    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var array
     */
    private $gridData;

    /**
     * Konsturktor
     *
     * @param Grid $grid
     * @param array $gridData
     */
    public function __construct(Grid $grid, array $gridData)
    {
        $this->grid = $grid;
        $this->gridData = $gridData;
    }

    /**
     * Generuje siatkę z danymi zależnymi od posiadanych ról użytkownika.
     *
     * @param ParpUser
     *
     * @return Grid
     */
    public function getGrid(): Grid
    {
        $source = new Vector($this->gridData, $this->getColumns());
        $grid = $this->grid;
        $grid->setSource($source);

        $rowAction = new RowAction(
            'Edytuj',
            'edit_path',
            false,
            null,
            ['class' => 'btn btn-warning btn-xs']
        );
        $rowAction
            ->setRouteParameters(['id'])
            ->setRouteParametersMapping([
            'id' => 'path'
            ])
        ;
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'Usuń',
            'remove_path',
            true,
            null,
            ['class' => 'btn btn-danger btn-xs']
        );
        $rowAction
            ->setRouteParameters(['id'])
            ->setRouteParametersMapping([
            'id' => 'path'
            ])
            ->setConfirmMessage('Na pewno usunąć ścieżkę?')
        ;
        $grid->addRowAction($rowAction);

        $grid
            ->setActionsColumnSize(60)
            ->setNoDataMessage('Brak wpisów.')
            ->setActionsColumnTitle('Akcje')
            ->isReadyForRedirect();

        return $grid;
    }

    /**
     * Zwraca kolumny siatki
     *
     * @return array
     */
    private function getColumns(): array
    {
        $columns = [
            new Column\NumberColumn([
                'id'      => 'id',
                'field'   => 'id',
                'source'  => true,
                'primary' => true,
                'title'   => 'ID',
                'size' => 10,
                'filterable' => false
            ]),
            new Column\TextColumn([
                'id'      => 'url',
                'field'   => 'url',
                'source'  => true,
                'title'   => 'Adres raportu',
                'filterable' => false,
                'size'    => 170,
            ]),
            new Column\TextColumn([
                'id'      => 'title',
                'field'   => 'title',
                'source'  => true,
                'title'   => 'Tytuł',
                'operatorsVisible' => false,
                'filterable' => false,
                'size'    => 150,
            ]),
            new Column\BooleanColumn([
                'id'      => 'isRepository',
                'field'   => 'isRepository',
                'source'  => true,
                'operatorsVisible' => false,
                'title'   => 'Czy jest folderem',
                'filterable' => false,
                'size'    => 60,
            ]),
        ];

        return $columns;
    }
}
