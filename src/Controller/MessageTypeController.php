<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Form\MessageTypeType;
use BisonLab\SakonninBundle\Form\FunctionAttributeType;
use BisonLab\SakonninBundle\Service\Functions as SakonninFunctions;

/**
 * MessageType controller.
 */
#[Route(path: '/sakonnin_messagetype')]
class MessageTypeController extends AbstractController
{
    use \BisonLab\CommonBundle\Controller\CommonControllerTrait;
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SakonninFunctions $sakonninFunctions
    ) {
    }

    /**
     * Lists all MessageType entities.
     */
    #[Route(path: '/', name: 'messagetype', methods: ['GET'])]
    public function indexAction()
    {
        $em = $this->getDoctrineManager();

        $entities = $em->getRepository(MessageType::class)->findAll();
        $parents = $em->createQueryBuilder()
                  ->select('mt')
                  ->from('BisonLab\SakonninBundle\Entity\MessageType', 'mt')
                  ->where('mt.parent is null')
                  ->orderBy('mt.name', 'ASC')
                  ->getQuery()
                  ->getResult();

        $entities = array();
        foreach ($parents as $p) {
            $entities[] = $p;
            if ($p->getChildren()->count() > 0)
                $entities = array_merge($entities, (array)$p->getChildren()->toArray());
        }
        return $this->render(
            '@BisonLabSakonnin/MessageType/index.html.twig',
            array('entities' => $entities));
    }

    /**
     * Creates a new MessageType.
     */
    #[Route(path: '/', name: 'messagetype_create', methods: ['POST'])]
    public function createAction(Request $request)
    {
        $messagetype = new MessageType();
        $form = $this->createCreateForm($messagetype);
        $form->handleRequest($request);
        $data = $form->getData();
        if (!$data->getParent() && !isset($request->request->get($form->getName())['create_group'])) {
            throw new \InvalidArgumentException('You must either check "This is a new group" to verify that you want a new group or choose a group in the drop down.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->persist($messagetype);
            $em->flush();
            return $this->redirectToRoute('messagetype_show', array('id' => $messagetype->getId()));
        }

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity' => $messagetype,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a MessageType.
     *
     * @param MessageType $messagetype
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MessageType $messagetype)
    {
        $form = $this->createForm(MessageTypeType::class, $messagetype, array(
            'action' => $this->generateUrl('messagetype_create'),
            'method' => 'POST',
        ));
        $this->_addFunctionsToForm($form);

        $form->add('create_group', CheckboxType::class, array('label' => "This is a new group.", 'mapped' => false, 'required' => false));

        return $form;
    }

    /**
     * Displays a form to create a new MessageType.
     */
    #[Route(path: '/new', name: 'messagetype_new', methods: ['GET'])]
    public function newAction()
    {
        $messagetype = new MessageType();
        $form   = $this->createCreateForm($messagetype);

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity' => $messagetype,
            'edit_form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a MessageType.
     */
    #[Route(path: '/{id}', name: 'messagetype_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showAction(MessageType $messagetype)
    {
        $em = $this->getDoctrineManager();

        return $this->render(
            '@BisonLabSakonnin/MessageType/show.html.twig', array(
            'entity' => $messagetype,
        ));
    }

    /**
     * Displays a form to edit an existing MessageType entity.
     */
    #[Route(path: '/{id}/edit', name: 'messagetype_edit', methods: ['GET'])]
    public function editAction(MessageType $messagetype)
    {
        $editForm = $this->createEditForm($messagetype);

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity'      => $messagetype,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a MessageType.
    *
    * @param MessageType $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MessageType $messagetype)
    {
        $form = $this->createForm(MessageTypeType::class, $messagetype, array(
            'action' => $this->generateUrl('messagetype_update',
                ['id' => $messagetype->getId()]),
        ));
        $this->_addFunctionsToForm($form);

        return $form;
    }

    /**
     * Edits an existing MessageType.
     */
    #[Route(path: '/{id}/update', name: 'messagetype_update', methods: ['POST'])]
    public function updateAction(Request $request, MessageType $messagetype)
    {
        $em = $this->getDoctrineManager();

        $editForm = $this->createEditForm($messagetype);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirectToRoute('messagetype_show', array('id' => $messagetype->getId()));
        }

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity'      => $messagetype,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a MessageType.
     */
    #[Route(path: '/{id}/delete', name: 'messagetype_delete', methods: ['POST'])]
    public function deleteAction(Request $request, $messagetype)
    {
        if ($this->isCsrfTokenValid('delete'.$messagetype->getId(), $request->request->get('_token')) && $messagetype->isDeleteable()) {
            $entityManager = $this->getDoctrineManager();
            $entityManager->remove($messagetype);
            $entityManager->flush();
            return $this->redirectToRoute('messagetype');
        }

        return $this->redirectToRoute('messagetype_show', array('id' => $messagetype->getId()));
    }

    private function _addFunctionsToForm(&$form)
    {
        $form->add('callback_function', ChoiceType::class, array(
                'required' => false, 
                'placeholder' => "None",
                'choices' => $this->sakonninFunctions->getCallbacksAsChoices()));
        $form->add('callbackAttributes', CollectionType::class,
                array(
                    'required' => false, 
                    'entry_type' => TextType::class,
                    'prototype' => true,
                    // 'prototype_name' => 'callbackAttributes',
                    'allow_add' => true,
                    'allow_delete' => true,
                ));
        $form->add('forward_function', ChoiceType::class, array(
                'required' => false, 
                'placeholder' => "None",
                'choices' => $this->sakonninFunctions->getForwardsAsChoices()));
        $form->add('forwardAttributes', CollectionType::class,
                array(
                    'required' => false, 
                    'entry_type' => TextType::class,
                    // NO need for key/value yet at least.
                    // 'type'=> new FunctionAttributeType(),
                    'prototype'=>true,
                    // 'prototype_name'=>'forwardAttributes',
                    'allow_add'=>true,
                    'allow_delete'=>true,
                ));
        return $form;
    }
}
