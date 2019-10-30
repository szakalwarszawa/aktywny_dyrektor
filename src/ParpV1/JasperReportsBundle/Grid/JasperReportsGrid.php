<?php

declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use ParpV1\AuthBundle\Security\ParpUser;
use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\ORM\EntityManager;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use APY\DataGridBundle\Grid\Column;
use ParpV1\JasperReportsBundle\Fetch\JasperFetch;
use APY\DataGridBundle\Grid\Action\RowAction;

/**
 * Siatka wyświetlająca dostępne raporty.
 */
class JasperReportsGrid
{
    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JasperFetch
     */
    private $jasperFetch;

    /**
     * Konsturktor
     *
     * @param Grid $grid
     */
    public function __construct(Grid $grid, EntityManager $entityManager, JasperFetch $jasperFetch)
    {
        $this->grid = $grid;
        $this->entityManager = $entityManager;
        $this->jasperFetch = $jasperFetch;
    }

    /**
     * Generuje siatkę z danymi zależnymi od posiadanych ról użytkownika.
     *
     * @param ParpUser
     *
     * @return Grid
     */
    public function generateForUser(ParpUser $parpUser): Grid
    {
        $data = $this->getData($parpUser);
        $columns = $this->getColumns();

        $source = new Vector($data, $columns);
        $grid = $this->grid;
        $grid
            ->setSource($source)
        ;

        $grid->hideColumns(['url']);
        $grid = $this->setRowActions($grid);
        $grid
            ->setNoDataMessage('Nie posiadasz dostępu do żadnego raportu.')
            ->setActionsColumnSize(90)
            ->setActionsColumnTitle('Generuj')
            ->isReadyForRedirect()
        ;

        return $grid;
    }

    /**
     * Ustawia akcje siatki.
     *
     * @param Grid
     *
     * @return Grid
     */
    private function setRowActions(Grid $grid): Grid
    {
        $rowAction = new RowAction(
            'Generuj',
            'prepare_report_settings',
            false,
            null,
            [
                'class' => 'btn btn-xs btn-info',
                'data-loadajaxmodal' => 'loadajaxmodal',
                'data-target' => '#generateReport'
            ]
        );
        $rowAction->setRouteParameters(['url']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        return $grid;
    }
    /**
     * Zwraca dane do siatki.
     *
     * @param ParpUser $parpUser
     * @param bool $returnAsArray
     *
     * @return array
     */
    private function getData(ParpUser $parpUser): array
    {
        $entityManager = $this->entityManager;
        $data = $entityManager
            ->getRepository(RolePrivilege::class)
            ->findPathsByRoles($parpUser->getRoles(), $this->jasperFetch)
        ;

        return $data;
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
                'size' => 20,
                'visible' => false,
            ]),
            new Column\TextColumn([
                'id'      => 'url',
                'field'   => 'url',
                'source'  => true,
                'title'   => 'Adres raportu'
            ]),
            new Column\TextColumn([
                'id'      => 'title',
                'field'   => 'title',
                'source'  => true,
                'title'   => 'Nazwa',
                'size'    => 200,
            ]),
            new Column\BooleanColumn([
                'id'      => 'isRepository',
                'field'   => 'isRepository',
                'source'  => true,
                'title'   => 'Powiernicy właściciela zasobu',
                'size'    => 200,
                'visible' => false
            ])
        ];

        return $columns;
    }
}
