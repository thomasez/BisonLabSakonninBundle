<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\SakonninTemplate;
use BisonLab\SakonninBundle\Form\SakonninTemplateType;

/**
 * Sakonnintemplate controller.
 */
#[Route(path: 'sakonnin_template')]
class SakonninTemplateController extends AbstractController
{
    use \BisonLab\CommonBundle\Controller\CommonControllerTrait;
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    public function __construct(
        private ManagerRegistry $managerRegistry
    ) {
    }

    /**
     * Lists all sakonninTemplate entities.
     */
    #[Route(path: '/', name: 'sakonnintemplate_index', methods: ['GET'])]
    public function indexAction()
    {
        $em = $this->getDoctrineManager();
        $sakonninTemplates = $em->getRepository(SakonninTemplate::class)->findAll();
        return $this->render('@BisonLabSakonnin/SakonninTemplate/index.html.twig',
            array( 'sakonninTemplates' => $sakonninTemplates,));
    }

    /**
     * Creates a new sakonninTemplate entity.
     */
    #[Route(path: '/new', name: 'sakonnintemplate_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, TranslatorInterface $translator)
    {
        $sakonninTemplate = new Sakonnintemplate();
        $default_lang_code = $translator->getLocale();
        $sakonninTemplate->setLangCode($default_lang_code);
        $form = $this->createForm(SakonninTemplateType::class, $sakonninTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrineManager();
            $entityManager->persist($sakonninTemplate);
            $entityManager->flush();

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
     */
    #[Route(path: '/{id:sakonninTemplate}', name: 'sakonnintemplate_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showAction(SakonninTemplate $sakonninTemplate)
    {
        return $this->render('@BisonLabSakonnin/SakonninTemplate/show.html.twig',
            array(
            'sakonninTemplate' => $sakonninTemplate,
        ));
    }

    /**
     * Displays a form to edit an existing sakonninTemplate entity.
     */
    #[Route(path: '/{id:sakonninTemplate}/edit', name: 'sakonnintemplate_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, SakonninTemplate $sakonninTemplate)
    {
        $editForm = $this->createForm(SakonninTemplateType::class, $sakonninTemplate);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrineManager()->flush();

            return $this->redirectToRoute('sakonnintemplate_show', array('id' => $sakonninTemplate->getId()));
        }

        return $this->render('@BisonLabSakonnin/SakonninTemplate/edit.html.twig',
            array(
                'sakonninTemplate' => $sakonninTemplate,
                'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a sakonninTemplate entity.
     */
    #[Route(path: '/{id:sakonninTemplate}/delete', name: 'sakonnintemplate_delete', methods: ['POST', 'DELETE'])]
    public function deleteAction(Request $request, SakonninTemplate $sakonninTemplate)
    {
        if ($this->isCsrfTokenValid('delete'.$sakonninTemplate->getId(), $request->request->get('_token')) && $sakonninTemplate->isDeleteable()) {
            $entityManager = $this->getDoctrineManager();
            $entityManager->remove($sakonninTemplate);
            $entityManager->flush();
            return $this->redirectToRoute('sakonnintemplate_index');
        }
        return $this->redirectToRoute('sakonnintemplate_show', array('id' => $sakonninTemplate->getId()));
    }
}
