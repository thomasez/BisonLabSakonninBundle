<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\File;

/**
 * File controller.
 *
 * @Route("/{access}/file", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class FileController extends CommonController
{
    /**
     * Lists all file entities.
     *
     * @Route("/", name="file_index")
     * @Method("GET")
     */
    public function indexAction($access)
    {
        $em = $this->getDoctrine()->getManager();
        $sf = $this->container->get('sakonnin.files');
        // Todo: paging or just show the last 20
        $files = $sf->getFilesForLoggedIn();

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $files, array('html' =>'file:_index.html.twig'));
        }
        return $this->render('BisonLabSakonninBundle:File:index.html.twig',
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
        return $this->render('BisonLabSakonninBundle:File:index.html.twig',
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
        $file = new File();
        $form = $this->createForm('BisonLab\SakonninBundle\Form\FileType', $file);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($file);
            $em->flush();

            return $this->redirectToRoute('file_show', array('id' => $file->getId()));
        }

        return $this->render('BisonLabSakonninBundle:File:new.html.twig',
            array('file' => $file, 'form' => $form->createView()
        ));
    }

    /**
     * Finds and displays a file entity.
     *
     * @Route("/{id}", name="file_show")
     * @Method("GET")
     */
    public function showAction(Request $request, File $file, $access)
    {
        $deleteForm = $this->createDeleteForm($file);

        return $this->render('BisonLabSakonninBundle:File:show.html.twig',
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
    public function editAction(Request $request, File $file, $access)
    {
        $deleteForm = $this->createDeleteForm($file);
        $editForm = $this->createForm('BisonLab\SakonninBundle\Form\FileType', $file);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('file_edit', array('id' => $file->getId()));
        }

        return $this->render('BisonLabSakonninBundle:File:edit.html.twig',
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
    public function deleteAction(Request $request, File $file, $access)
    {
        $form = $this->createDeleteForm($file);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($file);
            $em->flush();
        }

        return $this->redirectToRoute('file_index');
    }

    /**
     * Creates a form to delete a file entity.
     *
     * @param File $file The file entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(File $file, $access)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('file_delete', array('id' => $file->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
