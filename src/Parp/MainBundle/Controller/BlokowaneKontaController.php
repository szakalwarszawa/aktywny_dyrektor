<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

use Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Parp\MainBundle\Form\WniosekNadanieOdebranieZasobowType;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Parp\MainBundle\Exception\SecurityTestException;

/**
 * BlokowaneKontaController .
 *
 * @Route("/blokowanekonta")
 */
class BlokowaneKontaController extends Controller
{
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/lista/{ktore}", name="lista", defaults={"ktore" : "zablokowane"})
     * @Template()
     */
    public function listaAction($ktore = "zablokowane" /* nieobecni */)
    {
        $ldap = $this->get('ldap_service');
        $users = $ldap->getAllFromADIntW($ktore);
        print_r($users);
        die();
    }
    
}