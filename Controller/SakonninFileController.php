<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Form\Type\VichFileType;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * SakonninFile controller.
 *
 * @Route("/{access}/sakonnin_file", defaults={"access": "web"}, requirements={"access": "web|rest|ajax"})
 */
class SakonninFileController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all file entities.
     *
     * @Route("/", name="file_index", methods={"GET"})
     */
    public function indexAction($access)
    {
        $sf = $this->container->get('sakonnin.files');
        // Todo: paging or just show the last 20
        $sfiles = $sf->getFilesForLoggedIn();

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $sfiles, array('html' =>'file/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/SakonninFile/index.html.twig',
            array('files' => $sfiles));
    }

    /**
     * Creates a new file entity.
     *
     * @Route("/new", name="file_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $max_filesize = UploadedFile::getMaxFilesize();
        /*
         * OK, this annoys me. Somehow I should get a better message than
         * "The file "" does not exist" - exception when the file I attempt to
         * upload is bigger than max file size in php.ini.
         *
         * But I have not found out how it's handled by Symfony/Vich.
         * (But I can use functions from UploadedFile, it's still a hack.)
         */
        if (isset($_SERVER['CONTENT_LENGTH']) 
                && $_SERVER['CONTENT_LENGTH'] > $max_filesize) {
            return new Response( 'The file is probably too big for the system to handle. Either reduce size or configure the web server to handle bigger files', 400);
        }

        $sfile = new SakonninFile();
        $form = $this->createCreateForm($sfile);
        $data = $request->request->all();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sf = $this->container->get('sakonnin.files');
            $sf->storeFile($sfile, isset($data['file_context']) ? $data['file_context'] : array());

            if ($this->isRest($access)) {
                return new JsonResponse('OK Done', Response::HTTP_CREATED);
            }
            return $this->redirectToRoute('file_show', array('file_id' => $sfile->getFileId()));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('@BisonLabSakonnin/SakonninFile/new.html.twig',
            array(
                'max_filesize' => $max_filesize,
                'file' => $sfile,
                'form' => $form->createView()
        ));
    }

    /**
     * Finds and displays a file entity.
     *
     * @Route("/{file_id}", name="file_show", methods={"GET"}, requirements={"file_id"="\w{13}"})
     */
    public function showAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $deleteForm = $this->createDeleteForm($sfile);

        return $this->render('@BisonLabSakonnin/SakonninFile/show.html.twig',
            array(
            'file' => $sfile,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Download a file.
     *
     * @Route("/{file_id}/download", name="file_download", methods={"GET"})
     */
    public function downloadAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        // TODO: Add access control.
        $path = $this->getFilePath();
        $response = new BinaryFileResponse($path . "/" . $sfile->getStoredAs());
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sfile->getName());
        return $response;
    }

    /**
     * View a file.
     *
     * @Route("/{file_id}/view", name="file_view", methods={"GET"})
     */
    public function viewAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        // TODO: Add access control.
        $path = $this->getFilePath();
        $response = new BinaryFileResponse($path . "/" . $sfile->getStoredAs());
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        return $response;
    }

    /**
     * Create/cache thumbnail.
     *
     * @Route("/{file_id}/thumbnail/{x}/{y}", name="file_thumbnail", methods={"GET"})
     */
    public function thumbnailAction(Request $request, $access, $file_id, $x, $y)
    {
        $sf = $this->container->get('sakonnin.files');

        $sfile = $this->_getFile($file_id);

        if (!$sfile->getThumbnailable())
            $this->returnError($request, 'Not an image');
        // TODO: Add access control.
        // Gotta get the thumbnail then.
        $thumbfile = $sf->getThumbnailFilename($sfile, $x, $y);
        $response = new BinaryFileResponse($thumbfile);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        return $response;
    }

    /**
     * Displays a form to edit an existing file entity.
     *
     * @Route("/{file_id}/edit", name="file_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $deleteForm = $this->createDeleteForm($sfile);
        $action = $this->generateUrl('file_edit', array(
            'file_id' => $sfile->getFileId(),
            'access' => $access
            ));
        $editForm = $this->createForm(
            'BisonLab\SakonninBundle\Form\SakonninFileType',
            $sfile,
            ['action' => $action]
            );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrineManager()->flush();
            if ($this->isRest($access)) {
                return new JsonResponse([
                    "status" => "OK",
                    ], 200);
            }

            return $this->redirectToRoute('file_show', array('file_id' => $sfile->getFileId()));
        }

        if ($this->isRest($access)) {
            return $this
                    ->render('@BisonLabSakonnin/SakonninFile/_edit.html.twig',
                array(
                    'file' => $sfile,
                    'edit_form' => $editForm->createView(),
            ));
        }

        return $this->render('@BisonLabSakonnin/SakonninFile/edit.html.twig',
            array(
            'file' => $sfile,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a file entity.
     *
     * @Route("/{file_id}", name="file_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $form = $this->createDeleteForm($sfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->remove($sfile);
            $em->flush();
            if ($back = $request->request->get('back'))
                return $this->redirect($back);
        }

        return $this->redirectToRoute('file_index');
    }

    /**
     * Creates a form to create a File entry.
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
     * @param SakonninFile $sfile The file entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createDeleteForm(SakonninFile $sfile)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('file_delete', array('file_id' => $sfile->getFileId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function getFilePath()
    {
        return $this->container->getParameter('sakonnin.file_storage');
    }

    private function _getFile($file_id)
    {
        $sf = $this->container->get('sakonnin.files');
        if (!$sfile = $sf->getFiles(['fileid' => $file_id]))
            throw $this->createNotFoundException('File not found');
        return $sfile;
    }
}
