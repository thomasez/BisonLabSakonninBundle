<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Form\MessageTypeType;
use BisonLab\SakonninBundle\Form\FunctionAttributeType;

/**
 * MessageType controller.
 *
 * @Route("/sakonnin_messagetype")
 */
class MessageTypeController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all MessageType entities.
     *
     * @Route("/", name="messagetype", methods={"GET"})
     */
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
     * Creates a new MessageType entity.
     *
     * @Route("/", name="messagetype_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new MessageType();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $form->getData();
        if (!$data->getParent() && !isset($request->request->get($form->getName())['create_group'])) {
            throw new \InvalidArgumentException('You must either check "This is a new group" to verify that you want a new group or choose a group in the drop down.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute('messagetype_show', array('id' => $entity->getId()));
        }

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a MessageType entity.
     *
     * @param MessageType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MessageType $entity)
    {
        $form = $this->createForm(MessageTypeType::class, $entity, array(
            'action' => $this->generateUrl('messagetype_create'),
            'method' => 'POST',
        ));
        $this->_addFunctionsToForm($form);

        $form->add('create_group', CheckboxType::class, array('label' => "This is a new group.", 'mapped' => false, 'required' => false));

        return $form;
    }

    /**
     * Displays a form to create a new MessageType entity.
     *
     * @Route("/new", name="messagetype_new", methods={"GET"})
     */
    public function newAction()
    {
        $entity = new MessageType();
        $form   = $this->createCreateForm($entity);

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity' => $entity,
            'edit_form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a MessageType entity.
     *
     * @Route("/{id}", name="messagetype_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function showAction(MessageType $messagetype)
    {
        $em = $this->getDoctrineManager();

        $deleteForm = $this->createDeleteForm($messagetype->getid());

        return $this->render(
            '@BisonLabSakonnin/MessageType/show.html.twig', array(
            'entity'      => $messagetype,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing MessageType entity.
     *
     * @Route("/{id}/edit", name="messagetype_edit", methods={"GET"})
     */
    public function editAction(MessageType $messagetype)
    {
        $editForm = $this->createEditForm($messagetype);
        $deleteForm = $this->createDeleteForm($messagetype->getId());

        return $this->render(
            '@BisonLabSakonnin/MessageType/edit.html.twig', array(
            'entity'      => $messagetype,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a MessageType entity.
    *
    * @param MessageType $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MessageType $entity)
    {
        $form = $this->createForm(MessageTypeType::class, $entity, array(
            'action' => $this->generateUrl('messagetype_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        $this->_addFunctionsToForm($form);

        return $form;
    }

    /**
     * Edits an existing MessageType entity.
     *
     * @Route("/{id}", name="messagetype_update", methods={"PUT"})
     */
    public function updateAction(Request $request, MessageType $messagetype)
    {
        $em = $this->getDoctrineManager();

        $deleteForm = $this->createDeleteForm($messagetype->getId());
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
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a MessageType entity.
     *
     * @Route("/{id}", name="messagetype_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, $messagetype)
    {
        $form = $this->createDeleteForm($messagetype->getId());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($messagetype);
            $em->flush();
        }
        return $this->redirectToRoute('messagetype');
    }

    /**
     * Creates a form to delete a MessageType entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('messagetype_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function _addFunctionsToForm(&$form)
    {
        $form->add('callback_function', ChoiceType::class, array(
                'required' => false, 
                'placeholder' => "None",
                'choices' => $this->sakonmin_functions->getCallbacksAsChoices()));
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
                'choices' => $this->sakonmin_functions->getForwardsAsChoices()));
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
