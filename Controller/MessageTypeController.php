<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Form\MessageTypeType;
use BisonLab\SakonninBundle\Form\FunctionAttributeType;

/**
 * MessageType controller.
 *
 * @Route("/messagetype")
 */
class MessageTypeController extends Controller
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all MessageType entities.
     *
     * @Route("/", name="messagetype")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrineManager();

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
        return $this->render(
            'BisonLabSakonninBundle:MessageType:index.html.twig',
            array('entities' => $entities));
    }

    /**
     * Creates a new MessageType entity.
     *
     * @Route("/", name="messagetype_create")
     * @Method("POST")
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
            $em = $this->getDoctrineManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('messagetype_show', array('id' => $entity->getId())));
        }

        return $this->render(
            'BisonLabSakonninBundle:MessageType:edit.html.twig', array(
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
        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new MessageType entity.
     *
     * @Route("/new", name="messagetype_new")
     * @Method("GET")
     */
    public function newAction()
    {
        $entity = new MessageType();
        $form   = $this->createCreateForm($entity);

        return $this->render(
            'BisonLabSakonninBundle:MessageType:edit.html.twig', array(
            'entity' => $entity,
            'edit_form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a MessageType entity.
     *
     * @Route("/{id}", name="messagetype_show")
     * @Method("GET")
     */
    public function showAction($id)
    {
        $em = $this->getDoctrineManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render(
            'BisonLabSakonninBundle:MessageType:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing MessageType entity.
     *
     * @Route("/{id}/edit", name="messagetype_edit")
     * @Method("GET")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrineManager();
        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render(
            'BisonLabSakonninBundle:MessageType:edit.html.twig', array(
            'entity'      => $entity,
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

        $form->add('submit', SubmitType::class, array('label' => 'Update'));
        return $form;
    }

    /**
     * Edits an existing MessageType entity.
     *
     * @Route("/{id}", name="messagetype_update")
     * @Method("PUT")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrineManager();
        $entity = $em->getRepository('BisonLabSakonninBundle:MessageType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MessageType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('messagetype_show', array('id' => $id)));
        }

        return $this->render(
            'BisonLabSakonninBundle:MessageType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
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
            $em = $this->getDoctrineManager();
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
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    private function _addFunctionsToForm(&$form)
    {
        $sakonnin = $this->get('sakonnin.functions');

        $form->add('callback_function', ChoiceType::class, array(
                'required' => false, 
                'placeholder' => "None",
                'choices' => $sakonnin->getCallbacksAsChoices()));
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
                'choices' => $sakonnin->getForwardsAsChoices()));
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
