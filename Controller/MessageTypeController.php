<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Form\MessageTypeType;

/**
 * MessageType controller.
 *
 * @Route("/messagetype")
 */
class MessageTypeController extends Controller
{

    /**
     * Lists all MessageType entities.
     *
     * @Route("/", name="messagetype")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('BisonLabSakonninBundle:MessageType')->findAll();
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

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new MessageType entity.
     *
     * @Route("/", name="messagetype_create")
     * @Method("POST")
     * @Template("BisonLabSakonninBundle:MessageType:new.html.twig")
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

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('messagetype_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
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
        $form = $this->createForm(new MessageTypeType(), $entity, array(
            'action' => $this->generateUrl('messagetype_create'),
            'method' => 'POST',
        ));
        $this->_addFunctionsToForm($form);

        $form->add('create_group', 'checkbox', array('label' => "This is a new group.", 'mapped' => false, 'required' => false));
        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new MessageType entity.
     *
     * @Route("/new", name="messagetype_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MessageType();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a MessageType entity.
     *
     * @Route("/{id}", name="messagetype_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing MessageType entity.
     *
     * @Route("/{id}/edit", name="messagetype_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
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
    * Creates a form to edit a MessageType entity.
    *
    * @param MessageType $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MessageType $entity)
    {
        $form = $this->createForm(new MessageTypeType(), $entity, array(
            'action' => $this->generateUrl('messagetype_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        $this->_addFunctionsToForm($form);

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing MessageType entity.
     *
     * @Route("/{id}", name="messagetype_update")
     * @Method("PUT")
     * @Template("BisonLabSakonninBundle:MessageType:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('messagetype_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a MessageType entity.
     *
     * @Route("/{id}", name="messagetype_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MessageType entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('messagetype'));
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
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    private function _addFunctionsToForm(&$form)
    {
        $sakonnin = $this->get('sakonnin.functions');

        $form->add('callback_function', 'choice', array('choices' => $sakonnin->getCallbacksAsChoices()));
        $form->add('callbackAttributes', 'collection',
                array(
                    'type'=>'text',
                    'prototype'=>true,
                    'prototype_name'=>'forwardAttributes',
                    'allow_add'=>true,
                    'allow_delete'=>true,
                    'options'=>array(
                    )
                ));
        $form->add('forward_function', 'choice', array('choices' => $sakonnin->getForwardsAsChoices()));
        $form->add('forwardAttributes', 'collection',
                array(
                    'type'=>'text',
                    'prototype'=>true,
                    'prototype_name'=>'forwardAttributes',
                    'allow_add'=>true,
                    'allow_delete'=>true,
                    'options'=>array(
                    )
                ));

        return $form;

    }

}
