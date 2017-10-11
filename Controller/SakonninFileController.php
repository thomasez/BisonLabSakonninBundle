<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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
        $form = $this->createForm('BisonLab\SakonninBundle\Form\SakonninFileType', $file);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
dump($form);
            $sf = $this->container->get('sakonnin.files');
            $sf->storeFile($file, isset($data['message_context']) ? $data['message_context'] : array());

            return $this->redirectToRoute('file_show', array('id' => $file->getId()));
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
}
