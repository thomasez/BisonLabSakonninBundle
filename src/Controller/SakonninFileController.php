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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Vich\UploaderBundle\Form\Type\VichFileType;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\SakonninFile;
use BisonLab\SakonninBundle\Service\Files as SakonninFiles;

/**
 * SakonninFile controller.
 *
 * @Route("/{access}/sakonnin_file", defaults={"access": "web"}, requirements={"access": "web|rest|ajax"})
 */
class SakonninFileController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $managerRegistry;
    private $sakonninFiles;
    private $parameterBag;

    public function __construct(ManagerRegistry $managerRegistry, SakonninFiles $sakonninFiles, ParameterBagInterface $parameterBag, SerializerInterface $serializer)
    {
        $this->managerRegistry = $managerRegistry;
        $this->sakonninFiles = $sakonninFiles;
        $this->parameterBag = $parameterBag;

        // Push this "back to" the RestTrait included in CommonController.
        $this->serializer = $serializer;
    }


    /**
     * Lists all file entities.
     *
     * @Route("/", name="sakonninfile_index", methods={"GET"})
     */
    public function indexAction($access)
    {
        // Todo: paging or just show the last 20
        $sfiles = $this->sakonninFiles->getFilesForLoggedIn();

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $sfiles, array('html' =>'file/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/SakonninFile/index.html.twig',
            array('files' => $sfiles));
    }

    /**
     * Creates a new file.
     *
     * @Route("/new", name="sakonninfile_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $max_filesize = UploadedFile::getMaxFilesize();

        if ($request->isMethod('POST'))
            $request_data = $request->request->all();
        else
            $request_data = $request->query->all();
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

        $form = $this->sakonninFiles->getUploadForm($request_data);
        $form->handleRequest($request);
        $sfile = $form->getData();
        if ($form->isSubmitted()) {
            if ( $form->isValid()) {
                if ($request_data['sakonninfile']['multiple'] && !$sfile->getFile()) {
                    $files = $request->files->all()['sakonninfile']['files'] ?? [];
                    foreach ($files as $ulfile) {
                        $clfile = clone($sfile);
                        $clfile->setFile($ulfile);
                        $clfile->setFileId(uniqid());
                        $this->sakonninFiles->storeFile($clfile, $request_data['file_context'] ?? array());
                    }
                } else {
                    $this->sakonninFiles->storeFile($sfile, $request_data['file_context'] ?? array());
                }

                if ($this->isRest($access)) {
                    return new JsonResponse('OK Done', Response::HTTP_CREATED);
                }
                return $this->redirectToRoute('sakonninfile_show',
                    array('file_id' => $sfile->getFileId()));
            } elseif ($this->isRest($access)) {
                # We have a problem, and need to tell our user what it is.
                # Better make this a Json some day.
                return $this->returnErrorResponse("Validation Error", 400,
                    $this->handleFormErrors($form));
            }
        }
        if ($this->isRest($access)) {
            return $this->render('@BisonLabSakonnin/SakonninFile/_new.html.twig',
                array(
                    'max_filesize' => $max_filesize,
                    'file' => $sfile,
                    'file_context' => $request_data['file_context'] ?? null,
                    'formname' => $form->getName(),
                    'form' => $form->createView()
            ));
        }

        return $this->render('@BisonLabSakonnin/SakonninFile/new.html.twig',
            array(
                'max_filesize' => $max_filesize,
                'file_context' => $request_data['file_context'] ?? null,
                'formname' => $form->getName(),
                'file' => $sfile,
                'form' => $form->createView()
        ));
    }

    /**
     * Finds and displays a file.
     *
     * @Route("/{file_id}", name="sakonninfile_show", methods={"GET"}, requirements={"file_id"="\w{13}"})
     */
    public function showAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $this->denyAccessUnlessGranted('show', $sfile);
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
     * @Route("/{file_id}/download", name="sakonninfile_download", methods={"GET"})
     */
    public function downloadAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $this->denyAccessUnlessGranted('show', $sfile);
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
     * @Route("/{file_id}/view", name="sakonninfile_view", methods={"GET"})
     */
    public function viewAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $this->denyAccessUnlessGranted('show', $sfile);
        // TODO: Add access control.
        $path = $this->getFilePath();
        $response = new BinaryFileResponse($path . "/" . $sfile->getStoredAs());
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        return $response;
    }

    /**
     * Create/cache thumbnail.
     *
     * @Route("/{file_id}/thumbnail/{x}/{y}", name="sakonninfile_thumbnail", methods={"GET"})
     */
    public function thumbnailAction(Request $request, $access, $file_id, $x, $y)
    {
        $sfile = $this->_getFile($file_id);
        $this->denyAccessUnlessGranted('show', $sfile);

        if (!$sfile->getThumbnailable())
            $this->returnError($request, 'Not an image');
        // TODO: Add access control.
        // Gotta get the thumbnail then.
        $thumbfile = $this->sakonninFiles->getThumbnailFilename($sfile, $x, $y);
        $response = new BinaryFileResponse($thumbfile);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        return $response;
    }

    /**
     * Displays a form to edit an existing file.
     *
     * @Route("/{file_id}/edit", name="sakonninfile_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $this->denyAccessUnlessGranted('edit', $sfile);
        $deleteForm = $this->createDeleteForm($sfile);
        $action = $this->generateUrl('sakonninfile_edit', array(
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
            $entityManager = $this->getDoctrineManager();
            $entityManager->flush();
            if ($this->isRest($access)) {
                return new JsonResponse([
                    "status" => "OK",
                    ], 200);
            }

            return $this->redirectToRoute('sakonninfile_show', array('file_id' => $sfile->getFileId()));
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
     * Deletes a file.
     *
     * @Route("/{file_id}/delete", name="sakonninfile_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $file_id, $access)
    {
        $sfile = $this->_getFile($file_id);
        $form = $this->createDeleteForm($sfile);
        $form->handleRequest($request);
        $this->denyAccessUnlessGranted('delete', $sfile);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrineManager();
            $entityManager->remove($sfile);
            $entityManager->flush();
        }
        if ($back = $request->request->get('back'))
            return $this->redirect($back);

        return $this->redirectToRoute('sakonninfile_index');
    }

    /**
     * Creates a form to delete a file.
     *
     * @param SakonninFile $sfile The file
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createDeleteForm(SakonninFile $sfile)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sakonninfile_delete', array('file_id' => $sfile->getFileId())))
            ->getForm()
        ;
    }

    private function getFilePath()
    {
        return $this->parameterBag->get('sakonnin.file_storage');
    }

    private function _getFile($file_id)
    {
        if (!$sfile = $this->sakonninFiles->getFiles(['fileid' => $file_id]))
            throw $this->createNotFoundException('File not found');
        return $sfile;
    }
}
