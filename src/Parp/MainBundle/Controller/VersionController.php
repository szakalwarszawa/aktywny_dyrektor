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

    protected function getObjectHistory($repository, $id){
        $className = "Parp\\MainBundle\\Entity\\".$repository;
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('Parp\MainBundle\Entity\HistoriaWersji'); // we use default log entry class
        $entity = $em->find($className, $id);
        $logs = $repo->getLogEntries($entity);
        //$logs = array_reverse($logs);
        $metadata = $em->getClassMetadata($className);
        //echo "<pre>"; print_r($metadata); die();
        $cols = array();
        foreach($metadata->getFieldNames() as $fm){
            $cols[] = $fm;
        }
        $entities = array();
        foreach($logs as $log){
            $entityTemp = clone $entity;
            $repo->revert($entityTemp, $log->getVersion()/*version*/);
            $entities[] = array('repo' => $repository,'entity' => $entityTemp, 'log' => $log, 'cols' => $cols);
            //print_r($entities); die();
        }
        return $entities;
    }
    /**
     * @Route("/versionsHistory/{repository}/{id}", name="versionsHistory")
     * @Template()
     */
    public function versionsHistoryAction($repository, $id)
    {
        //nowe
        $pomijajRelacje = array('WniosekNadanieOdebranieZasobowViewer','WniosekNadanieOdebranieZasobowEditor','WniosekNadanieOdebranieZasobow','WniosekHistoriaStatusow');
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('softdeleteable');
        $className = "Parp\\MainBundle\\Entity\\".$repository;
        $entity = $em->getRepository($className)->find($id);
        $entities = $this->getObjectHistory($repository, $id);
        $pid = $entity->getParent() ? $entity->getParent()->getId() : 0;
        if($pid > 0){
            $entities2 = $this->getObjectHistory($repository, $pid);
            $entities = array_merge($entities, $entities2);   
        }
        $metadata = $em->getClassMetadata($className);
        
        foreach($metadata->getAssociationMappings() as $m){
            if(!isset($m['joinColumns'])){
                
                $repos = explode("\\", $m['targetEntity']);
                $repo = $repos[count($repos) - 1];
                if(!in_array($repo, $pomijajRelacje)){
                    $f = "get".ucfirst($m['fieldName']);
                    $ents = $entity->{$f}();  
                              
                    foreach($ents as $ed){
                        //echo "<pre>";var_dump($f);// die();
                        $entities2 = $this->getObjectHistory($repo, $ed->getId());
                        $entities = array_merge($entities, $entities2);                
                    }
                }
            }
        }
        $pliki = $em->getRepository('ParpMainBundle:Plik')->findBy(array(
            'obiekt' => $repository,
            'obiektId' => $id    
        ));
        foreach($pliki as $p){
            $entities2 = $this->getObjectHistory('Plik', $p->getId());
            $entities = array_merge($entities, $entities2); 
        }
        $komentarze = $em->getRepository('ParpMainBundle:Komentarz')->findBy(array(
            'obiekt' => $repository,
            'obiektId' => $id    
        ));
        foreach($komentarze as $k){
            $entities2 = $this->getObjectHistory('Komentarz', $k->getId());
            $entities = array_merge($entities, $entities2); 
        }
        usort($entities, function($a, $b){
            return $a['log']->getLoggedAt() <  $b['log']->getLoggedAt();
        });
        
        $result = array();
        foreach ($entities as $data) {
          $idd = $data['log']->getLoggedAt()->format("YmdhIs");
          //if (isset($result[$id])) {
             //print_r($data); 
             $result[$idd]['data'][] = $data;
             $result[$idd]['obiekt'][$this->get('rename_service')->objectTitles($data['repo'])] = $this->get('rename_service')->objectTitles($data['repo']);             
             $result[$idd]['user'][$data['log']->getUsername()] = $data['log']->getUsername();
             $result[$idd]['operacje'][$data['log']->getAction()] = $this->get('renameService')->actionTitles($data['log']->getAction());
             $result[$idd]['id'] = $data['log']->getLoggedAt()->format("Y-m-d H:i:s");
          //} else {
             //$result[$id] = array($data);
          //}
        }
        //echo "<pre>";        \Doctrine\Common\Util\Debug::dump($result,10); die();
        
        $em->getFilters()->enable('softdeleteable');
        
        $now = new \Datetime();
        return array(
            'result' => $result,
            'entities' => $entities,
            'entityname' => $repository,
            'id' => $id,
            'now' => $now->format("Y-m-d H:i:s")
        );
    }
    
    
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