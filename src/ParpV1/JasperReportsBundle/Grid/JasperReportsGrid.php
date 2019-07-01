<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use ParpV1\AuthBundle\Security\ParpUser;
use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\ORM\EntityManager;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Columns;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\JasperReportsBundle\Fetch\JasperReportFetch;
use Doctrine\Common\Collections\ArrayCollection;
use APY\DataGridBundle\Grid\Action\RowAction;

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

        $rowAction = new RowAction(
            'Generuj',
            'report_print', null, null
        );
        $rowAction->setRouteParameters(['url']);
        $rowAction->setRouteParametersMapping([
            'url' => 'reportUri'
        ]);
        $grid->addRowAction($rowAction);

        $grid->isReadyForRedirect();

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
