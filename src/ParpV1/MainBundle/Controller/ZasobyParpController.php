<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
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

use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Form\ZasobyType;

/**
 * Zasoby controller.
 *
 * @Route("/zasoby")
 */
class ZasobyParpController extends Controller
{

    /**
     * Lists all Zasoby entities.
     *
     * @Route("/users/{zasobId}", name="zasoby_parp")
     * @Template()
     */
    public function listUsersAction($zasobId)
    {
        
        $em = $this->getDoctrine()->getManager();
        $res = $em->getRepository('Parp\MainBundle\Entity\UserZasoby')->findUsersByZasobId($zasobId);
        
        //print_r($res[0]->getADUser()); die();
        
        return $this->render(
            "ParpMainBundle:Zasoby:zasobyUsers.html.twig",
            array(
                "users" => $res
            )
        );
    }
}
