<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\SakonninTemplate;

/**
 * Sakonnintemplate controller.
 *
 * @Route("sakonnin_template")
 */
class SakonninTemplateController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all sakonninTemplate entities.
     *
     * @Route("/", name="sakonnintemplate_index", methods={"GET"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrineManager();

        $sakonninTemplates = $em->getRepository(SakonninTemplate::class)->findAll();

        return $this->render('@BisonLabSakonnin/SakonninTemplate/index.html.twig',
            array( 'sakonninTemplates' => $sakonninTemplates,));
    }

    /**
     * Creates a new sakonninTemplate entity.
     *
     * @Route("/new", name="sakonnintemplate_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $sakonninTemplate = new Sakonnintemplate();
        $default_lang_code = $this->container->get('translator')->getLocale();
        $sakonninTemplate->setLangCode($default_lang_code);
        $form = $this->createForm('BisonLab\SakonninBundle\Form\SakonninTemplateType', $sakonninTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->persist($sakonninTemplate);
            $em->flush();

            return $this->redirectToRoute('sakonnintemplate_show', array('id' => $sakonninTemplate->getId()));
        }

        return $this->render('@BisonLabSakonnin/SakonninTemplate/new.html.twig',
            array(
            'sakonninTemplate' => $sakonninTemplate,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a sakonninTemplate entity.
     *
     * @Route("/{id}", name="sakonnintemplate_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function showAction(SakonninTemplate $sakonninTemplate)
    {
        $deleteForm = $this->createDeleteForm($sakonninTemplate);

        return $this->render('@BisonLabSakonnin/SakonninTemplate/show.html.twig',
            array(
            'sakonninTemplate' => $sakonninTemplate,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing sakonninTemplate entity.
     *
     * @Route("/{id}/edit", name="sakonnintemplate_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, SakonninTemplate $sakonninTemplate)
    {
        $deleteForm = $this->createDeleteForm($sakonninTemplate);
        $editForm = $this->createForm('BisonLab\SakonninBundle\Form\SakonninTemplateType', $sakonninTemplate);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrineManager()->flush();

            return $this->redirectToRoute('sakonnintemplate_show', array('id' => $sakonninTemplate->getId()));
        }

        return $this->render('@BisonLabSakonnin/SakonninTemplate/edit.html.twig',
            array(
                'sakonninTemplate' => $sakonninTemplate,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a sakonninTemplate entity.
     *
     * @Route("/{id}", name="sakonnintemplate_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, SakonninTemplate $sakonninTemplate)
    {
        $form = $this->createDeleteForm($sakonninTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->remove($sakonninTemplate);
            $em->flush();
        }

        return $this->redirectToRoute('sakonnintemplate_index');
    }

    /**
     * Creates a form to delete a sakonninTemplate entity.
     *
     * @param SakonninTemplate $sakonninTemplate The sakonninTemplate entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(SakonninTemplate $sakonninTemplate)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sakonnintemplate_delete', array('id' => $sakonninTemplate->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
