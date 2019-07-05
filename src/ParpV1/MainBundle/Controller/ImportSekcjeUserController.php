<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use ParpV1\MainBundle\Entity\ImportSekcjeUser;

/**
 * ImportSekcjeUser controller.
 *
 * @Route("/importsekcjeuser")
 */
class ImportSekcjeUserController extends Controller
{

    /**
     * Lists all ImportSekcjeUser entities.
     *
     * @Route("/index", name="importsekcjeuser")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository(ImportSekcjeUser::class)->findAll();

        $source = new Entity(ImportSekcjeUser::class);

        $grid = $this->get('grid');
        $grid->setSource($source);

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        // Edycja konta
        $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'importsekcjeuser_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="far fa-trash-alt"></i> Skasuj', 'importsekcjeuser_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));



        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }

    /**
     * Finds and displays a ImportSekcjeUser entity.
     *
     * @Route("/{id}", name="importsekcjeuser_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(ImportSekcjeUser::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ImportSekcjeUser entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }
}
