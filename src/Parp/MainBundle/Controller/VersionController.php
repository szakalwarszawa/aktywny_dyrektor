<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Form\EngagementType;
use Parp\MainBundle\Form\UserEngagementType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Action\MassAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Parp\MainBundle\Entity\UserZasoby;
use Parp\MainBundle\Form\UserZasobyType;
use Parp\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class VersionController extends Controller
{

    /**
     * @Route("/versions/{repository}/{id}", name="versions")
     * @Template()
     */
    public function versionsAction($repository, $id)
    {
        $className = "Parp\\MainBundle\\Entity\\".$repository;
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('Parp\MainBundle\Entity\HistoriaWersji'); // we use default log entry class
        $entity = $em->find($className, $id);
        $logs = $repo->getLogEntries($entity);
        //$logs = array_reverse($logs);
        $entities = array();
        foreach($logs as $log){
            $entityTemp = clone $entity;
            $repo->revert($entityTemp, $log->getVersion()/*version*/);
            $entities[] = array('entity' => $entityTemp, 'log' => $log);
            //print_r($log->getData()); die();
        }
        $metadata = $em->getClassMetadata($className);
        $cols = array();
        foreach($metadata->getFieldNames() as $fm){
            $cols[] = $fm;
        }
        
        //print_r($cols); die();
        $now = new \Datetime();
        return array(
            'entities' => $entities,
            'entityname' => $repository,
            'id' => $id,
            'now' => $now->format("Y-m-d H:i:s"),
            'columns' => $cols
        );
    }
    
}