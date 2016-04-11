<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Entity\UserUprawnienia;
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
use Parp\MainBundle\Entity\HistoriaWersji;
use Symfony\Component\Finder\Finder;
/**
 * Zasoby controller.
 *
 * @Route("/dev")
 */
class DevController extends Controller
{

    /**
     * @Route("/index", name="index")
     * @Template()
     */
    public function indexAction()
    {
        die('dev');
    }
    /**
     * @Route("/generujCreateHistoriaWersji", name="generujCreateHistoriaWersji")
     * @Template()
     */
    public function generujCreateHistoriaWersjiAction()
    {
        $entities = array();
        $em = $this->getDoctrine()->getManager();
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $now = new \Datetime();
        
        $username = "undefined";
        $securityContext = $this->get('security.context');
        if (null !== $securityContext && null !== $securityContext->getToken() && $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $username = ($securityContext->getToken()->getUsername());            
        }
        $request = $this->get('request');
        $url = $request->getUri();
        //print_r($url);
        $route = $request->get('_route');
        
        foreach ($meta as $m) {
            $all = $em->getRepository($m->getName())->findAll();
            $mn = $m->getName();//str_replace("Parp:MainBundle", "ParpMainBundle", str_replace("\\", ":", $m->getName()));
            //print_r($mn); die();
            $all = $result = $this->getDoctrine()
               ->getRepository($mn)
               ->createQueryBuilder('e')
               ->select('e')
               ->getQuery()
               ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
            
            foreach($all as $e){
                $hw = new HistoriaWersji();
                $hw->setAction('create');
                $hw->setLoggedAt($now);
                $hw->setObjectId($e['id']);
                $hw->setObjectClass($m->getName());
                $hw->setVersion(1);
                $d = ($e);
                //print_r($d); die();
                $hw->setData($d);
                $hw->setUsername($username);
                $hw->setUrl($url);
                $hw->setRoute($route);
                $em->persist($hw);
                
            }
            
            $entities[] = array('name' => $m->getName(), 'count' => count($all));
        }
        $em->flush();
        print_r($entities);
        die('generujCreateHistoriaWersji');
    }
    /**
     * @Route("/uzupelnijAdnotacjeHistoriiWersji", name="uzupelnijAdnotacjeHistoriiWersji")
     * @Template()
     */
    public function uzupelnijAdnotacjeHistoriiWersjiAction()
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../../src/*/*/Entity');
        
        $unrelatedClasses = array();
        
        foreach ($finder as $file) {
            if(strpos($file->getRelativePathname(), "~") != strlen($file->getRelativePathname()) -1
            && strstr($file->getRelativePathname(), "Repository") === false
            && strstr($file->getRelativePathname(), "DateEntityClass") === false
            && strstr($file->getRelativePathname(), "OrderItemDTO") === false
            ){
                $f = str_replace("/var/www/parp/aktywny_dyrektor/src", "", $file->getRealpath());
                $f = str_replace("/", "\\", $f);            
                $f = str_replace(".php", "", $f);
                if($f != '\Parp\MainBundle\Entity\HistoriaWersji'){
                    //die($f);
                    $h = file_get_contents($file->getRealpath());
                    
                    if(strstr($h, '@Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")') !== false){
                        echo ('mamy zasob Z gedmo '.$file->getRealpath());
                    }else{
                        echo('mamy zasob bez gedmo '.$file->getRealpath()."<br>\n");
                        $patterns = array (
                            '/( \*\/)(\n)(class)/', 
                            '/(     \*\/)(\n)(    private \$)([^i][^d])/'
                        );
                        $replace = array (
                            ' * @Gedmo\\Mapping\\Annotation\\Loggable(logEntryClass="Parp\\MainBundle\\Entity\\HistoriaWersji")$2$1$2$3', 
                            '     * @Gedmo\\Mapping\\Annotation\\Versioned$2$1$2$3$4'
                        );
                        $h = preg_replace($patterns, $replace, $h);
                        file_put_contents($file->getRealpath(), $h);
                    }
                }
                
                //print_r($h); die();
                
            }
        }
        die('generujCreateHistoriaWersji');
    }
    /**
     * Kasuje wszedzie deletedAt z forms
     *
     * @Route("/fix_forms/",defaults={}, name="dev_fix_forms")
     * @Template()
     */
    public function fixFormsAction()
    {
        $updateFiles = false;
        
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../../src/*/*/Form');
        
        foreach ($finder as $file) {
            // Print the absolute path
            print $file->getRealpath()."\n 1 ";
            $c = file_get_contents($file->getRealpath());
            $deletedAt = true;
            if(strstr($c, "->add('deletedAt',null,array(") === false){
                $deletedAt = false;
            }
            
            if($deletedAt){
                $s = array("->add('deletedAt',null,array(");
                $r = array("->add('deletedAt','hidden',array(");
                $c = str_replace($s, $r, $c);
                if($updateFiles)
                    file_put_contents($file->getRealpath(), $c);
            }
            
            print $file->getRelativePathname()."\n 3 ".($deletedAt ? "ma deleted at" : "NIE MA")." <br/>";
        }
    }

    /**
     * @Route("/groupConcat", name="groupConcat")
     * @Template()
     */
    public function groupConcatAction()
    {
        $sql = "select group_concat(e.samaccountname) from Parp\\MainBundle\\Entity\\Entry e";
        $em = $this->getDoctrine()->getEntityManager();
        $result= $em->createQuery($sql)->getResult();
        \Doctrine\Common\Util\Debug::dump($result);
        die('groupConcat');
    }

}    