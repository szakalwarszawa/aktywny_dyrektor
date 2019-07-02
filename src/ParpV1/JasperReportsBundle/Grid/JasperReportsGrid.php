<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use ParpV1\AuthBundle\Security\ParpUser;
use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\ORM\EntityManager;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use APY\DataGridBundle\Grid\Column;
use ParpV1\JasperReportsBundle\Fetch\JasperReportFetch;
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
     * @var JasperReportFetch
     */
    private $jasperReportFetch;

    /**
     * Konsturktor
     *
     * @param Grid $grid
     */
    public function __construct(Grid $grid, EntityManager $entityManager, JasperReportFetch $jasperReportFetch)
    {
        $this->grid = $grid;
        $this->entityManager = $entityManager;
        $this->jasperReportFetch = $jasperReportFetch;
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
            'PDF',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-xs btn-info']
        );
        $rowAction->setRouteParameters(['url', 'format' => 'pdf']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'XLS',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-xs btn-info']
        );
        $rowAction->setRouteParameters(['url', 'format' => 'xls']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'DOCX',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-xs btn-info']
        );
        $rowAction->setRouteParameters(['url', 'format' => 'docx']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'PPTX',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-xs btn-info']
        );
        $rowAction->setRouteParameters(['url', 'format' => 'pptx']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction(
            'CSV',
            'report_print',
            false,
            null,
            ['class' => 'btn btn-xs btn-info']
        );
        $rowAction->setRouteParameters(['url', 'format' => 'csv']);
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
            ->findPathsByRoles($parpUser->getRoles(), $this->jasperReportFetch)
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
