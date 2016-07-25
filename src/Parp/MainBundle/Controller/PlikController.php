<?php

namespace Parp\MainBundle\Controller;

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

use Parp\MainBundle\Entity\Plik;
use Parp\MainBundle\Form\PlikType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plik controller.
 *
 * @Route("/plik")
 */
class PlikController extends Controller
{
    
    
    /**
     * Download a file
     *
     * @Route("/download/{id}",defaults={"id" = null}, name="plik_download")
     * @Method("GET")
     * @Template()
     */
    public function downloadAction($id = null)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('softdeleteable');
        $doc = $em->getRepository('ParpMainBundle:Plik')->find($id);
    
        if (!$doc) {
            throw $this->createNotFoundException('Unable to find Plik entity.');
        }
        
        $filePath = $doc->getFilePath();
        $filename = $doc->getFile();
        // check if file exists
        if (!file_exists($filePath)) {
            echo($filePath);
            throw $this->createNotFoundException();
            
        }
        
        // Generate response
        $response = new Response();
        
        
        // Set headers
        $response->headers->set('Cache-Control', 'private');
        //$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        //$mtype = finfo_file($finfo, $filePath);
        $response->headers->set('Content-type', 'application/octet-stream'); //$mtype);
        //finfo_close($finfo);
        
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '";');
        $response->headers->set('Content-length', filesize($filePath));
        
        //print_r($response->headers);
        //die();  
        // Send headers before outputting anything
        $response->sendHeaders();
        
        $response->setContent(readfile($filePath));
        $em->getFilters()->enable('softdeleteable');
        die();
        /*

        // prepare BinaryFileResponse
        $response = new BinaryFileResponse($filePath);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename,
            iconv('UTF-8', 'ASCII//TRANSLIT', $filename)
        );

        return $response;
        */
    }
    

    /**
     * Lists all Plik entities.
     *
     * @Route("/index/{obiekt}/{obiektId}", defaults={"obiekt"="all", "obiektId"=0}, name="plik")
     * @Template()
     */
    public function indexAction($obiekt, $obiektId)
    {
        //die('a');
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Plik')->findAll();
    
        $source = new Entity('ParpMainBundle:Plik');
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $obiekt, $obiektId)
            {
                $query->andWhere($tableAlias.'.obiekt = \''.$obiekt.'\' and '.$tableAlias.'.obiektId = \''.$obiektId.'\'');
            }
        );
        $grid = $this->get('grid');
        $grid->setRouteUrl($this->generateUrl('plik', array('obiekt' => $obiekt, 'obiektId' => $obiektId)));
        $grid->setSource($source);
    
        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);
    
        // Zdejmujemy filtr
        $grid->getColumn('akcje')->setFilterable(false)->setSafe(true);
    
        // Edycja konta

        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'plik_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'plik_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
        
        
        $rowAction4 = new RowAction('<i class="fa fa-file"></i> Pobierz', 'plik_download');
        $rowAction4->setColumn('akcje');
        $rowAction4->addAttribute('class', 'btn btn-primary btn-xs');
    

       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
        $grid->addRowAction($rowAction4);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse(array('obiekt' => $obiekt, 'obiektId' => $obiektId));
    }
    /**
     * Creates a new Plik entity.
     *
     * @Route("/", name="plik_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Plik:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Plik();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->upload();
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Plik został załączony.');
                return $this->redirect($this->generateUrl(strtolower($entity->getObiekt())."_edit", array('id' => $entity->getObiektId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Plik entity.
     *
     * @param Plik $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Plik $entity)
    {
        $form = $this->createForm(new PlikType(), $entity, array(
            'action' => $this->generateUrl('plik_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Załącz Plik', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Plik entity.
     *
     * @Route("/new/{obiekt}/{obiektId}", name="plik_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request, $obiekt, $obiektId)
    {
        $entity = new Plik();
        $entity->setObiekt($obiekt);
        $entity->setObiektId($obiektId);
        $form   = $this->createCreateForm($entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'returnUrl' => $request->headers->get('referer') 
        );
    }

    /**
     * Finds and displays a Plik entity.
     *
     * @Route("/{id}", name="plik_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Plik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Plik entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Plik entity.
     *
     * @Route("/{id}/edit", name="plik_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Plik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Plik entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Plik entity.
    *
    * @param Plik $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Plik $entity)
    {
        $form = $this->createForm(new PlikType(), $entity, array(
            'action' => $this->generateUrl('plik_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Plik entity.
     *
     * @Route("/{id}", name="plik_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Plik:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Plik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Plik entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->upload();
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('plik_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Plik entity.
     *
     * @Route("/{id}", name="plik_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('ParpMainBundle:Plik')->find($id);
        $url = $this->generateUrl(strtolower($entity->getObiekt())."_edit", array('id' => $entity->getObiektId()));

        if ($form->isValid()) {

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Plik entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($url);
    }

    /**
     * Creates a form to delete a Plik entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('plik_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Plik','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
