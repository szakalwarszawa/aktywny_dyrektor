<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Action\RowAction;

/**
 * Siatka PathRoleGrid
 */
class PathRoleGrid
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
     * Generuje siatkę z danymi.
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
            'edit_role_privilege',
            false,
            null,
            ['class' => 'btn btn-warning btn-xs']
        );
        $rowAction
            ->setRouteParameters(['id'])
            ->setRouteParametersMapping([
            'id' => 'rolePrivilege'
            ])
        ;
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'Usuń',
            'remove_role_privilege',
            true,
            null,
            ['class' => 'btn btn-danger btn-xs']
        );
        $rowAction
            ->setRouteParameters(['id'])
            ->setRouteParametersMapping([
            'id' => 'rolePrivilege'
            ])
            ->setConfirmMessage('Na pewno usunąć wpis uprawnienia?')
        ;
        $grid->addRowAction($rowAction);

        $grid
            ->setActionsColumnSize(70)
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
                'size' => 25,
                'filterable' => false
            ]),
            new Column\TextColumn([
                'id'      => 'roleName',
                'field'   => 'roleName',
                'source'  => true,
                'title'   => 'Nazwa roli',
                'operatorsVisible' => false,
                'size'    => 200,
            ]),
            new Column\TextColumn([
                'id'      => 'reports',
                'field'   => 'reports',
                'source'  => true,
                'operatorsVisible' => false,
                'title'   => 'Raporty'
            ]),
        ];

        return $columns;
    }
}
