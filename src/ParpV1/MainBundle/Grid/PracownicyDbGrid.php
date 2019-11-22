<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Grid;

use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;

/**
 * Grid z listą pracowników D/B
 */
class PracownicyDbGrid
{
    /**
     * @var Grid
     */
    private $grid;

    /**
     * Kolumny do ukrycia.
     *
     * @return array
     */
    private static function kolumnydoUkrycia(): array
    {
        return [
            'manager',
            //'accountDisabled',
            //'info',
            'description',
            // 'division',
            // 'thumbnailphoto',
            'useraccountcontrol',
            // 'samaccountname',
            'initials',
            'accountExpires',
            'accountexpires',
            'email',
            'lastlogon',
            'cn',
            'distinguishedname',
            'memberOf',
            'roles',
            'mailnickname',
            'department',
            'extensionAttribute10'
        ];
    }

    /**
     * Publiczny konstruktor.
     *
     * @param Grid $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * Generuje grida z listą pracowników D/B.
     *
     * @param array $AdUsers
     *
     * @return Grid
     */
    public function getUserGrid($AdUsers): Grid
    {
        $source = new Vector($AdUsers);

        $source->setId('samaccountname');

        $grid = $this->grid;
        $grid->setSource($source);
        $grid->hideColumns(self::kolumnyDoUkrycia());

        $grid->getColumn('samaccountname')
            ->setTitle('Nazwa użytkownika')
            ->setOperators(['like'])
            ->setOperatorsVisible(false)
            ->setPrimary(true);
        $grid->getColumn('name')
            ->setTitle('Nazwisko imię')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('initials')
            ->setTitle('Inicjały')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('title')
            ->setTitle('Stanowisko')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('department')
            ->setTitle('Jednostka')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('info')
            ->setTitle('Sekcja')
            ->setSize(550)
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('lastlogon')
            ->setTitle('Ostatnie logowanie')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('accountexpires')
            ->setTitle('Umowa wygasa')
            ->setOperators(['like'])
            ->setOperatorsVisible(false);
        $grid->getColumn('thumbnailphoto')
            ->setTitle('Zdj.')
            ->setSize(45)
            ->setFilterable(false);
        $grid->getColumn('isDisabled')
            ->setTitle('Konto wyłączone')
            ->setOperators(['like'])
            ->setClass('text-center')
            ->setVisible(false)
            ->setOperatorsVisible(false);
        $grid->getColumn('division')
            ->setTitle('Symbol sekcji')
            ->setSize(90)
            ->setOperatorsVisible(false);

        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);
        $grid->getColumn('akcje')
            ->setFilterable(false)
            ->setSafe(true)
            ->setClass('text-center')
            ->setSize(135);

        $rowAction = new RowAction('<i class="fad fa-layer-group"></i> Uprawnienia', 'zasoby_pracownika');
        $rowAction->setColumn('akcje')
            ->setRouteParameters(['samaccountname'])
            ->addAttribute('class', 'btn btn-success btn-xs');
        $grid->addRowAction($rowAction);

        $grid->setLimits(100)
            ->isReadyForRedirect();

        return $grid;
    }
}
