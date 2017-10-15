<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Vich\UploaderBundle\Form\Type\VichFileType;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * SakonninFile controller.
 *
 * @Route("/{access}/file", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class SakonninFileController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all file entities.
     *
     * @Route("/", name="file_index")
     * @Method("GET")
     */
    public function indexAction($access)
    {
        $em = $this->getDoctrineManager();
        $sf = $this->container->get('sakonnin.files');
        // Todo: paging or just show the last 20
        $files = $sf->getFilesForLoggedIn();

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $files, array('html' =>'file:_index.html.twig'));
        }
        return $this->render('BisonLabSakonninBundle:SakonninFile:index.html.twig',
            array('files' => $files));
    }

    /**
     * Lists all Message entities of a certain type.
     * Warning: This can be *a lot* of files.
     *
     * @Route("/filetype/{id}", name="file_filetype")
     * @Method("GET")
     */
    public function listByTypeAction(Request $request, $access, FileType $fileType)
    {
        $sm = $this->container->get('sakonnin.files');
        $files = $messageType->getMessages(true);
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $files, array('html' =>'BisonLabSakonninBundle:Message:_index.html.twig'));
        }
        return $this->render('BisonLabSakonninBundle:SakonninFile:index.html.twig',
            array('entities' => $files));
    }

    /**
     * Creates a new file entity.
     *
     * @Route("/new", name="file_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $file = new SakonninFile();
        $form = $this->createCreateForm($file);
        $data = $request->request->all();

        $this->handleForm($form, $request, $access);

        if ($form->isSubmitted() && $form->isValid()) {
            $sf = $this->container->get('sakonnin.files');
            $sf->storeFile($file, isset($data['file_context']) ? $data['file_context'] : array());

            if ($this->isRest($access)) {
                return $this->returnRestData($request, "OK Done");
            }
            return $this->redirectToRoute('file_show', array('id' => $file->getId()));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('BisonLabSakonninBundle:SakonninFile:new.html.twig',
            array('file' => $file, 'form' => $form->createView()
        ));
    }

    /**
     * Finds and displays a file entity.
     *
     * @Route("/{id}", name="file_show")
     * @Method("GET")
     */
    public function showAction(Request $request, SakonninFile $file, $access)
    {
        $deleteForm = $this->createDeleteForm($file, $access);

        return $this->render('BisonLabSakonninBundle:SakonninFile:show.html.twig',
            array(
            'file' => $file,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Download a file.
     *
     * @Route("/{id}/download", name="file_download")
     * @Method("GET")
     */
    public function downloadAction(Request $request, SakonninFile $file, $access)
    {
        // TODO: Add access control.
        $path = $this->getFilePath();
        $response = new BinaryFileResponse($path . "/" . $file->getStoredAs());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $response;
    }

    /**
     * View a file.
     *
     * @Route("/{id}/view", name="file_view")
     * @Method("GET")
     */
    public function viewAction(Request $request, SakonninFile $file, $access)
    {
        // TODO: Add access control.
        $path = $this->getFilePath();
        $response = new BinaryFileResponse($path . "/" . $file->getStoredAs());
        return $response;
    }

    /**
     * Create/cache thumbnail.
     *
     * @Route("/{id}/thumbnail/{x}/{y}", name="file_thumbnail")
     * @Method("GET")
     */
    public function thumbnailAction(Request $request, $access, SakonninFile $file, $x, $y)
    {
        if ($file->getFileType() != 'IMAGE')
            $this->returnError($request, 'Not an image');
        // TODO: Add access control.
        // Gotta get the thumbnail then.
        $sf = $this->container->get('sakonnin.files');
        $thumbfile = $sf->getThumbnailFilename($file, $x, $y);
        $response = new BinaryFileResponse($thumbfile);
        return $response;
    }

    /**
     * Displays a form to edit an existing file entity.
     *
     * @Route("/{id}/edit", name="file_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SakonninFile $file, $access)
    {
        $deleteForm = $this->createDeleteForm($file, $access);
        $editForm = $this->createForm('BisonLab\SakonninBundle\Form\SakonninFileType', $file);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrineManager()->flush();

            return $this->redirectToRoute('file_edit', array('id' => $file->getId()));
        }

        return $this->render('BisonLabSakonninBundle:SakonninFile:edit.html.twig',
            array(
            'file' => $file,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a file entity.
     *
     * @Route("/{id}", name="file_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, SakonninFile $file, $access)
    {
        $form = $this->createDeleteForm($file, $access);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->remove($file);
            $em->flush();
        }

        return $this->redirectToRoute('file_index');
    }

    /**
     * Creates a form to create a Message entity.
     *
     * @param File $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createCreateForm(SakonninFile $entity)
    {
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\SakonninFileType::class, $entity, array(
            'action' => $this->generateUrl('file_new'),
            'method' => 'POST',
        ));
        $form->add('file', VichFileType::class, [
            'required' => true,
            'allow_delete' => true,
        ]);
        $form->add('submit', SubmitType::class, array('label' => 'Engage'));

        return $form;
    }

    /**
     * Creates a form to delete a file entity.
     *
     * @param SakonninFile $file The file entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(SakonninFile $file, $access)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('file_delete', array('id' => $file->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function getFilePath()
    {
        return $this->container->getParameter('vich_uploader.mappings')['sakonnin_file']['upload_destination'];
    }
}
