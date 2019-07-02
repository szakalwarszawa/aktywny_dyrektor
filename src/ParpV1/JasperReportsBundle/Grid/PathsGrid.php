<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use ParpV1\AuthBundle\Security\ParpUser;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column;
use ParpV1\JasperReportsBundle\Entity\Path;
use APY\DataGridBundle\Grid\Source\Vector;

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

       /* $rowAction = new RowAction(
            'Generuj',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-primary']
        );
        $rowAction->setRouteParameters(['url']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);*/

        $grid->setNoDataMessage('Brak wpisów.');
        $grid->isReadyForRedirect();

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
                'size' => 40,
                'filterable' => false
            ]),
            new Column\TextColumn([
                'id'      => 'url',
                'field'   => 'url',
                'source'  => true,
                'title'   => 'Adres raportu',
                'operatorsVisible' => false,
                'size'    => 200,
            ]),
            new Column\TextColumn([
                'id'      => 'title',
                'field'   => 'title',
                'source'  => true,
                'title'   => 'Tytuł',
                'operatorsVisible' => false,
                'size'    => 200,
            ]),
            new Column\BooleanColumn([
                'id'      => 'isRepository',
                'field'   => 'isRepository',
                'source'  => true,
                'operatorsVisible' => false,
                'title'   => 'Czy jest folderem'
            ]),
        ];

        return $columns;
    }
}
