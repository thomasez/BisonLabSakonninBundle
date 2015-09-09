<?php

namespace BisonLab\SakonninBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Form\MessageType as MessageForm;


/**
 * Message controller.
 *
 * @Route("/{access}/message", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class MessageController extends CommonController
{

    /**
     * Lists all Message entities.
     *
     * @Route("/", name="message")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('BisonLabSakonninBundle:Message')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a Message entity.
     *
     * @Route("/{id}", name="message_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $access, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:Message')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Message entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }

    /**
     * Lists all Messages with that context.
     *
     * @Route("/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}", name="message_context_search")
     * @Method("GET")
     * @Template()
     */
    public function searchContextGetAction(Request $request, $access, $system, $object_name, $external_id)
    {
        // Not yet.
        $context_conf = $this->container->getParameter('app.contexts');
        $conf = $context_conf['BisonLabSakonninBundle']['Message'];
        $conf['entity'] = "BisonLabSakonninBundle:Message";
        $conf['show_template'] = "BisonLabSakonninBundle:Message:show.html.twig";
        $conf['list_template'] = "BisonLabSakonninBundle:Message:index.html.twig";
        return $this->contextGetAction(
                    $request, $conf, $access, $system, $object_name, $external_id);

    }

    /**
     * Creates a new MessageType entity.
     *
     * @Route("/create", name="message_create")
     * @Method("POST")
     * @Template("BisonLabSakonninBundle:Message:new.html.twig")
     */
    public function createAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        $form = $sm->getCreateForm($request->request->all());
        $this->handleForm($form, $request, $access);

        if ($form->isValid()) {
            // Ok, it's valid. We'll send this to postMessage then.
            $entity = $form->getData();
            $sm->postMessage($entity);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, $entity);
            }
            return $this->redirect($this->generateUrl('message_show', array('id' => $entity->getId())));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400, $this->handleFormErrors($form));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Message entity.
     *
     * @param MessageType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createCreateForm(Message $entity)
    {
        $form = $this->createForm(new MessageForm(), $entity, array(
            'action' => $this->generateUrl('message_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Send'));

        return $form;
    }

}
