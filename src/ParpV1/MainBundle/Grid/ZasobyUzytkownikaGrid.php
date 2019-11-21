<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\UserZasoby;

/**
 * Grid z uprawnieniami do zasobów pracownika
 */
class ZasobyUzytkownikaGrid
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
     * Kolumny widoczne dla aktywnych uprawnień.
     *
     * @return array
     */
    private static function kolumnyZasobyAktywne(): array
    {
        return [
            'zasobOpis',
            'modul',
            'poziomDostepu',
            'aktywneOd',
            'bezterminowo',
            'aktywneDo',
            'powodNadania',
            'wniosek_n_numer',
            'akcja',
        ];
    }

    /**
     * Kolumny widoczne dla nieaktywnych uprawnień.
     *
     * @return array
     */
    private static function kolumnyZasobyNieaktywne(): array
    {
        return [
            'zasobOpis',
            'modul',
            'poziomDostepu',
            'aktywneOd',
            'bezterminowo',
            'aktywneDo',
            'powodOdebrania',
            'dataOdebrania',
            'wniosek_o_numer',
            'akcja',
        ];
    }

    /**
     * Publiczny konstruktor.
     *
     * @param Grid          $grid
     * @param EntityManager $entityManager
     */
    public function __construct(Grid $grid, EntityManager $entityManager)
    {
        $this->grid = $grid;
        $this->entityManager = $entityManager;
    }

    /**
     * Generuje grida z uprawnieniami użytkownika.
     *
     * @param string $samaccountname
     * @param bool $aktywne
     *
     * @return Grid
     */
    public function generateForUser(string $samaccountname, bool $aktywne): Grid
    {
        $userResources = $this->getZasobyUzytkownika($samaccountname, $aktywne);
        $source = new Vector($userResources, $this->getColumns());

        $grid = $this->grid;
        $grid->setSource($source);

        $grid->getColumn('modul')->manipulateRenderCell(
            function ($value) {
                    return str_replace(';', '; ', $value);
            }
        );
        $grid->getColumn('poziomDostepu')->manipulateRenderCell(
            function ($value) {
                    return str_replace(';', '; ', $value);
            }
        );

        $actionsColumn = new ActionsColumn('akcja', 'Akcje');
        $grid->addColumn($actionsColumn);

        $grid->getColumn('akcja')
                ->setFilterable(false)
                ->setSafe(true)
                ->setClass('text-center')
                ->setSize(135)
        ;
        $grid->getColumn('bezterminowo')
                ->setClass('text-center');

        $rowAction = new RowAction(
            'Wniosek',
            'wnioseknadanieodebraniezasobow_show',
            false,
            '_self',
            [
                'class' => 'btn btn-warning btn-xs',
                'title' => 'Zobacz wniosek o ' . ($aktywne ? 'nadanie' : 'odebranie') . ' uprawnień',
            ]
        );

        if ($aktywne) {
            $rowAction
                ->setTitle('<i class="fas fa-file-search"></i> wn. o nadanie upr.')
                ->addManipulateRender(
                    function ($action, $row) {
                        if ($row->getField('wniosek_n_id') === '') {
                            return null;
                        } else {
                            return $action;
                        }
                    }
                )
                ->setRouteParameters(['wniosek_n_id'])
                ->setRouteParametersMapping([
                    'wniosek_n_id' => 'id'
                ]);
            $grid->setVisibleColumns(ZasobyUzytkownikaGrid::kolumnyZasobyAktywne());
        } else {
            $rowAction
                ->setTitle('<i class="fas fa-file-search"></i> wn. o odebranie upr.')
                ->addManipulateRender(
                    function ($action, $row) {
                        if ($row->getField('wniosek_o_id') === '') {
                            return null;
                        } else {
                            return $action;
                        }
                    }
                )
                ->setRouteParameters(['wniosek_o_id'])
                ->setRouteParametersMapping([
                    'wniosek_o_id' => 'id'
                ]);
            $grid->setVisibleColumns(ZasobyUzytkownikaGrid::kolumnyZasobyNieaktywne());
        }

        $rowAction->setColumn('akcja');
        $grid
            ->addRowAction($rowAction)
            ->setLimits([50, 100])
            ->setNoDataMessage('Brak uprawnień do wyświetlenia.')
        ;
        $grid->isReadyForRedirect();
        // $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));

        return $grid;
    }

    /**
     * Pobieranie danych urawnień użytkownika
     *
     * @param string $samaccountname
     * @param bool   $aktywne
     *
     * @return array
     */
    private function getZasobyUzytkownika(string $samaccountname, bool $aktywne): array
    {
        $ktore = $aktywne ? 'aktywne' : 'nieaktywne';
        $uprawnieniaAktywne = [];

        $userResources =
            $this
                ->entityManager
                ->getRepository(UserZasoby::class)
                ->findZasobyUzytkownika(strtolower($samaccountname));

        if (empty($userResources)) {
            return [];
        }

        foreach ($userResources[$ktore] as $key => $userResource) {
            $uprawnieniaAktywne[$key] = [
                'id' => $userResource['user_zasob']->getId(),
                'zasobOpis' => $userResource['user_zasob']->getZasobOpis(),
                'modul' => $userResource['user_zasob']->getModul(),
                'poziomDostepu' => $userResource['user_zasob']->getPoziomDostepu(),
                'aktywneOd' => $userResource['user_zasob']->getAktywneOd(),
                'bezterminowo' => $userResource['user_zasob']->getBezterminowo(),
                'aktywneDo' => $userResource['user_zasob']->getAktywneDo(),
                'powodNadania' => $userResource['user_zasob']->getPowodNadania(),
                'powodOdebrania' => $userResource['user_zasob']->getPowodOdebrania(),
                'dataOdebrania' => $userResource['user_zasob']->getDataOdebrania(),
            ];
            if (null !== $userResource['user_zasob']->getWniosek()) {
                $uprawnieniaAktywne[$key]['wniosek_n_id'] = $userResource['user_zasob']->getWniosek()->getId();
                $uprawnieniaAktywne[$key]['wniosek_n_numer'] = $userResource['user_zasob']->getWniosek()->getWniosek()->getNumer();
            } else {
                $uprawnieniaAktywne[$key]['wniosek_n_id'] = '';
                $uprawnieniaAktywne[$key]['wniosek_n_numer'] = '';
            }
            if (null !== $userResource['user_zasob']->getWniosekOdebranie()) {
                $uprawnieniaAktywne[$key]['wniosek_o_id'] = $userResource['user_zasob']->getWniosekOdebranie()->getId();
                $uprawnieniaAktywne[$key]['wniosek_o_numer'] = $userResource['user_zasob']->getWniosekOdebranie()->getWniosek()->getNumer();
            } else {
                $uprawnieniaAktywne[$key]['wniosek_o_id'] = '';
                $uprawnieniaAktywne[$key]['wniosek_o_numer'] = '';
            }
        }

        return $uprawnieniaAktywne;
    }

    /**
     * Definiuje kolumny w siatce danych.
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
                'visible' => false,
            ]),
            new Column\TextColumn([
                'id'      => 'zasobOpis',
                'field'   => 'zasobOpis',
                'source'  => true,
                'title'   => 'Nazwa zasobu',
                'primary' => true,
                'size'    => 230,
            ]),
            new Column\TextColumn([
                'id'      => 'modul',
                'field'   => 'modul',
                'source'  => true,
                'title'   => 'Moduł',
                'size'    => 180,
            ]),
            new Column\TextColumn([
                'id'      => 'poziomDostepu',
                'field'   => 'poziomDostepu',
                'source'  => true,
                'title'   => 'Poziom dostępu',
                'size'    => 180,
            ]),
            new Column\DateColumn([
                'id'      => 'aktywneOd',
                'field'   => 'aktywneOd',
                'source'  => true,
                'title'   => 'Aktywne od',
                'format' => 'Y-m-d',
                'size'    => 70,
            ]),
            new Column\BooleanColumn([
                'id'      => 'bezterminowo',
                'field'   => 'bezterminowo',
                'source'  => true,
                'title'   => 'Bezterminowo',
            ]),
            new Column\DateColumn([
                'id'      => 'aktywneDo',
                'field'   => 'aktywneDo',
                'source'  => true,
                'title'   => 'Aktywne do',
                'format' => 'Y-m-d',
                'size'    => 70,
            ]),
            new Column\TextColumn([
                'id'      => 'powodNadania',
                'field'   => 'powodNadania',
                'source'  => true,
                'title'   => 'Powód nadania',
                'size'    => 280,
            ]),
            new Column\TextColumn([
                'id'      => 'powodOdebrania',
                'field'   => 'powodOdebrania',
                'source'  => true,
                'title'   => 'Powód odebrania',
                'size'    => 280,
            ]),
            new Column\DateColumn([
                'id'      => 'dataOdebrania',
                'field'   => 'dataOdebrania',
                'source'  => true,
                'title'   => 'Data odebrania',
                'format' => 'Y-m-d',
                'size'    => 70,
            ]),
            new Column\TextColumn([
                'id'      => 'wniosek_n_numer',
                'field'   => 'wniosek_n_numer',
                'source'  => true,
                'title'   => 'Wniosek o nadanie upr.',
                'size'    => 120,
            ]),
            new Column\TextColumn([
                'id'      => 'wniosek_o_numer',
                'field'   => 'wniosek_o_numer',
                'source'  => true,
                'title'   => 'Wniosek o odebranie upr.',
                'size'    => 120,
            ]),
        ];

        return $columns;
    }
}
