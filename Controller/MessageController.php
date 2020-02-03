<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Entity\MessageContext;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Message controller.
 *
 * @Route("/{access}/sakonnin_message", defaults={"access": "web"}, requirements={"access": "web|rest|ajax"})
 */
class MessageController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all Message entities.
     *
     * don't like this. Using just "/" which would be logical makes the
     * routecomponent match every GET below this one.
     * Another option is to put this one as the last one.
     * @Route("/list", name="messages_list", methods={"GET"})
     */
    public function listAction(Request $request, $access)
    {
        $this->denyAccessUnlessGranted('index', new Message());
        $sm = $this->container->get('sakonnin.messages');
        
        // Gotta add criterias then.
        $criterias = [];
        if ($message_types = $request->get('message_types')) {
            $criterias['message_types'] = $message_types;
        }
        if ($states = $request->get('states')) {
            $criterias['states'] = $states;
        }

        $messages = [];
        if (!empty($criterias))
            $messages = $sm->getMessages($criterias);
        
        if ('DESC' == $request->get('sort')) {
            $messages = array_reverse($messages);
        }

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages));
    }
    /**
     * So wrong path name.
     * @Route("/me", name="message", methods={"GET"})
     */
    public function myMessagesAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        // Todo: paging or just show the last 20
        $messages = $sm->getMessagesForLoggedIn(array('not_message_type' => 'PM'));
        // Gotta set the messages as read.
        /* No, not while listing the messages, only when viewving separately.
        foreach ($messages as $message) {
            if ($message->getState() == "UNREAD")
                $message->setState('READ');
            $em = $this->getDoctrineManager();
            $em->flush();
        }
        */
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Lists all Message entities.
     *
     * @Route("/unread", name="message_unread")
     */
    public function unreadAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        $messages = $sm->getMessagesForLoggedIn(array('state' => 'UNREAD'));
        // Gotta set the messages as read.
        /* Unread means not read, this should not automatically mean it's read.
        foreach ($messages as $message) {
            $message->setState('READ');
        }
        $em = $this->getDoctrineManager();
        $em->flush();
        */
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages,
                array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages, 
                  'unread_starts_at' => $unread_starts_at));
    }

    /**
     * Lists all Message entities.
     *
     * @Route("/pm", name="pm_list", methods={"GET"})
     */
    public function pmAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        $messages = $sm->getMessagesForLoggedIn(array('message_type' => 'PM'));
        $unread_starts_at = null;
        foreach ($messages as $message) {
            if ($message->getState() == "UNREAD") {
                $message->setState('READ');
                $unread_starts_at = $message->getId();
            }
        }
        $em = $this->getDoctrineManager();
        $em->flush();
        if ($this->isRest($access)) {
            $data = array('messages' => $messages,
                'unread_starts_at' => $unread_starts_at);
            return $this->returnRestData($request, $data,
                array('html' =>'@BisonLabSakonnin/Message/_pm_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages, 
                  'unread_starts_at' => $unread_starts_at));
    }

    /**
     * Lists all Message entities of a certain type.
     * Warning: This can be *a lot* of messages.
     *
     * @Route("/messagetype/{id}", name="message_messagetype", methods={"GET"})
     */
    public function listByTypeAction(Request $request, $access, MessageType $messageType)
    {
        $sm = $this->container->get('sakonnin.messages');
        $messages = $messageType->getMessages(true);
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Finds and displays a Message entity.
     *
     * @Route("/{id}", name="message_show", methods={"GET"}, requirements={"id"="(\d+|\w{13})"})
     */
    public function showAction(Request $request, $access, $id)
    {
        $em = $this->getDoctrineManager();
        // Hack. The contextGetAction in CommonBundle is not as smart as it
        // looks.
        $message = null;
        if ($id instanceof Message) {
            $message = $id;
        } elseif (is_numeric($id)) {
            $message = $em->getRepository('BisonLabSakonninBundle:Message')
                    ->find($id);
        } else {
            $message = $em->getRepository('BisonLabSakonninBundle:Message')
                    ->findOneBy(array('message_id' => $id));
        }
        if (!$message) {
            return $this->returnNotFound($request, 'Unable to find Message.');
        }
        $this->denyAccessUnlessGranted('show', $message);
        // If it's shown to receiver, it's read.
        $sm = $this->container->get('sakonnin.messages');
        $user = $this->getUser();
        // Not sure I want to set READ automatically. But UNREAD/READ is not
        // archived, which the users should set themselves.
        if ($message->getTo() == $user->getId()) {
            $message->setState("READ");
            $em = $this->getDoctrineManager();
            $em->flush();
        }
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_show.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/show.html.twig',
            array('entity' => $message));
    }

    /**
     * Displays a form to edit an existing person entity.
     *
     * @Route("/{id}/edit", name="message_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, $access, Message $message)
    {
        $this->denyAccessUnlessGranted('edit', $message);
        $action = $this->generateUrl('message_edit', array(
            'id' => $message->getId(),
            'reload_after_post' => $request->get('reload_after_post'),
            'access' => $access
            ));
        $editForm = $this
            ->createForm('BisonLab\SakonninBundle\Form\MessageType', $message, 
                ['action' => $action]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            if ($editForm->isValid()) {
                $this->getDoctrineManager()->flush();

                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK", 200));
                }
                return $this->redirectToRoute('message_show',
                    array('access' => $access, 'id' => $message->getId()));
           } elseif ($this->isRest($access)) {
                $errors = $this->handleFormErrors($editForm);
                return new JsonResponse(array("status" => "ERROR",
                    'errors' => $errors), 422);
            }
        }
        if ($this->isRest($access)) {
            return $this
                    ->render('@BisonLabSakonnin/Message/_edit.html.twig',
                array(
                    'message' => $message,
                    'reload_after_post' => $request->get('reload_after_post'),
                    'action' => $action,
                    'edit_form' => $editForm->createView(),
            ));
        }
        $deleteForm = $this->createDeleteForm($message);
        return $this->render('@BisonLabSakonnin/Message/edit.html.twig',
            array(
                'message' => $message,
                'reload_after_post' => $request->get('reload_after_post'),
                'action' => $action,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing person entity.
     *
     * @Route("/{id}/state/{state}", name="message_state", methods={"POST"})
     */
    public function stateAction(Request $request, $access, Message $message, $state)
    {
        $this->denyAccessUnlessGranted('edit', $message);
        $message->setState($state);
        $this->getDoctrineManager()->flush($message);

        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK",
                'state' => $message->getState()),
                Response::HTTP_OK);
        }
        return $this->redirectToRoute('message_show',
            array('access' => $access, 'id' => $message->getId()));
    }

    /**
     * Lists all Messages with that context.
     *
     * @Route("/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}", name="message_context_search", methods={"GET"})
     */
    public function searchContextGetAction(Request $request, $access, $system, $object_name, $external_id)
    {
        // Search/Index - basically same same. For now at least.
        /* But it has a very annoying drawback;
         * It more or less does not work.
         * The reason is simple: I have to be able to filter every entity on
         * granted or not. And I can do that on two ways:
         *
         * Not use contextGetAction from the CommonBundle and make my own here
         *   and filter every single entity.
         * Rewrite contextGetAction to check every entity and filter.
         *   Which means quite alot more calls and slower response.
         * Add a better is_granted to twig and filter there.
         *   is_granted in twog only supports a role check, not the symfony
         *   security voter, which is quite odd. Someone else should have felt
         *   this need aswell.
         */
        $this->denyAccessUnlessGranted('index', new Message());
        $context_conf = $this->container->getParameter('app.contexts');
        $conf = $context_conf['BisonLabSakonninBundle']['Message'];
        $conf['entity'] = "BisonLabSakonninBundle:Message";

        // If it's REST, but HTML, we'll be returning HTML content, but not a
        // complete page.
        if ($this->isRest($access)) {
            $conf['show_template'] = "@BisonLabSakonnin/Message/_show.html.twig";
            $conf['list_template'] = "@BisonLabSakonnin/Message/_index.html.twig";
        } else {
            $conf['show_template'] = "@BisonLabSakonnin/Message/show.html.twig";
            $conf['list_template'] = "@BisonLabSakonnin/Message/index.html.twig";
        }
        return $this->contextGetAction(
            $request, $conf, $access, $system, $object_name, $external_id);
    }

    /**
     * Creates a new PM
     * (Which does look more and more like the usual createMessage.)
     *
     * @Route("/pm", name="pm_create", methods={"POST"})
     */
    public function createPmAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');

        $data = $request->request->all();
        $form = $sm->getCreatePmForm($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
            $this->denyAccessUnlessGranted('create', $message);
            $em = $this->getDoctrineManager();
            $user = $this->getUser();

            $message_type = $data['message_type'] ?: "PM";
            $message->setMessageType(
                $em->getRepository('BisonLabSakonninBundle:MessageType')
                    ->findOneByName($message_type)
            );

            $message->setToType('INTERNAL');
            if ($data['to_userid']) {
                $message->setTo($data['to_userid']);
            }

            /*
             * TODO: Maybe put the test in other places aswell?
             */
            if ($irt = $request->query->get('in_reply_to')) {
                if (!$irt_msg = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $irt)))
                    throw $this->createAccessDeniedException('No or bad reply .');
                if ($irt_msg->getTo() != $user->getId())
                    throw $this->createAccessDeniedException('No or bad reply .');
                $message->setInReplyTo($irt_msg);
            }
            $message->setFromType('INTERNAL');
            $message->setFrom($user->getId());

            $sm->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, "OK Done");
            }
            return $this->redirectToRoute('message_show',
                array('access' => $access, 'id' => $message->getId()));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('@BisonLabSakonnin/Message/new.html.twig',
            array('entity' => $message, 'form'   => $form->createView()
            ));
    }

    /**
     * Creates a new Message
     *
     * @Route("/create", name="message_create", methods={"POST"})
     */
    public function createAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        $em = $this->getDoctrineManager();
        if ($parsed = json_decode($request->getContent(), true)) {
            $data = $parsed['message_data'];
            if (!isset($data['from_type'])) {
                $data['from_type'] = "EXTERNAL";
            }
            // Gotta do some security check. This is a hack, but it should
            // work..
            if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                $message = new  Message();
                $message->setMessageType($message_type);
                $this->denyAccessUnlessGranted('create', $message);
            } else {
                // No messaagetype? naaah.
                throw $this->createAccessDeniedException('No access, no message type found for message.');
            }
            $message = $sm->postMessage($data, isset($parsed['message_context']) ? $parsed['message_context'] : array());
            if ($message) {
                return $this->returnRestData($request, $message->__toArray(), null, 204);
            }
            return $this->returnErrorResponse("Validation Error", 400);
        }

        $data = $request->request->all();
        $form = $sm->getCreateForm($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ok, it's valid. We'll send this to postMessage then.
            $message = $form->getData();
            $this->denyAccessUnlessGranted('create', $message);
            if (!$message->getMessageType() && isset($data['message_type'])) {
                $em = $this->getDoctrineManager();
                $message->setMessageType(
                    $em->getRepository('BisonLabSakonninBundle:MessageType')
                        ->findOneByName($data['message_type'])
                );
            }
            if (!$message->getFromType()) {
                if (isset($data['from_type']))
                    $message->setFromType($data['from_type']);
                else
                    $message->setFromType("EXTERNAL");
            }
            $sm->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, $message->__toArray(), null, 204);
            }
            return $this->redirectToRoute('message_show',
                array('access' => $access, 'id' => $message->getId()));
        }

        if ($this->isRest($access)) {
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('@BisonLabSakonnin/Message/new.html.twig',
            array('entity' => $message, 'form'   => $form->createView()));
    }

    /**
     * Deletes a message entity.
     *
     * @Route("/{id}", name="message_delete", methods={"DELETE"}, requirements={"id"="(\d+|\w{13})"})
     */
    public function deleteAction(Request $request, $access, Message $message)
    {
        $this->denyAccessUnlessGranted('edit', $message);
        $form = $this->createDeleteForm($message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrineManager();
            $em->remove($message);
            $em->flush($message);
        }
        if ($this->isRest($access))
            return new JsonResponse(array("status" => "DELETED"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Check for unread messages
     *
     * @Route("/check_unread", name="check_unread", methods={"GET"})
     */
    public function checkUnreadAction(Request $request, $access)
    {
        $em = $this->getDoctrineManager();
        $sm = $this->container->get('sakonnin.messages');
        $user = $this->getUser();

        $repo = $em->getRepository('BisonLabSakonninBundle:Message');
        $messages = $sm->getMessagesForLoggedIn(array('state' => 'UNREAD'));
        if ($messages) {
            return $this->returnRestData($request, array('amount' => count($messages)));
        }
        // return $this->returnRestData($request, false);
        return $this->returnRestData($request, array('amount' => 0));
    }

    /**
     * Creates a new person entity.
     *
     * @Route("/new", name="message_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $message = new Message();
        if ($message_type = $request->get('message_type')) {
            $em = $this->getDoctrineManager();
            if (is_numeric($message_type)) {
                $message->setMessageType(
                    $em->getRepository('BisonLabSakonninBundle:MessageType')
                        ->find($message_type)
                );
            } else {
                $message->setMessageType(
                    $em->getRepository('BisonLabSakonninBundle:MessageType')
                        ->findOneByName($message_type)
                );
            }
        }

        $form = $this->createForm('BisonLab\SakonninBundle\Form\MessageType',
            $message);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $sm = $this->container->get('sakonnin.messages');
                if ($context_data = $request->get('message_context')) {
                    $message_context = new MessageContext($context_data);
                    $message->addContext($message_context);
                }
                $sm->postMessage($message);
                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK", 200));
                }
                return $this->redirectToRoute('message_show',
                    array('access' => $access, 'id' => $message->getId()));
           } elseif ($this->isRest($access)) {
                $errors = $this->handleFormErrors($form);
                return new JsonResponse(array("status" => "ERROR",
                    'errors' => $errors), 422);
            }
        }
        $action = $this->generateUrl('message_new', array(
            'reload_after_post' => $request->get('reload_after_post'),
            'access' => $access
            ));
        if ($this->isRest($access)) {
            return $this
                    ->render('@BisonLabSakonnin/Message/_new.html.twig',
                array(
                    'message' => $message,
                    'reload_after_post' => $request->get('reload_after_post') ?? true,
                    'action' => $action,
                    'elements' => $request->get('elements') ?? null,
                    'context' => $request->get('context') ?? null,
                    'form' => $form->createView(),
            ));
        }
        return $this->render('@BisonLabSakonnin/Message/new.html.twig',
            array(
                'message' => $message,
                'reload_after_post' => $request->get('reload_after_post') ?? true,
                'elements' => $request->get('elements') ?? null,
                'context' => $request->get('context') ?? null,
                'action' => $action,
                'form' => $form->createView(),
        ));
    }

    /**
     * Adding a context to an existing message.
     *
     * @Route("/{id}/add_context", name="message_add_context", methods={"POST"})
     */
    public function addContextAction(Request $request, $access, Message $message)
    {
        $this->denyAccessUnlessGranted('edit', $message);

        if (!$system = $request->get('system'))
            throw new \InvalidArgumentException("No system given");
        if (!$object_name = $request->get('object_name'))
            throw new \InvalidArgumentException("No object name given");
        if (!$external_id = $request->get('external_id'))
            throw new \InvalidArgumentException("No external id given");

        $context = new MessageContext();
        $context->setSystem($system);
        $context->setObjectName($object_name);
        $context->setExternalId($external_id);
        $context->setOwner($message);
        $em = $this->getDoctrineManager();
        $em->persist($context);
        $em->flush($context);

        if ($this->isRest($access))
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Remove just the context
     *
     * @Route("/{id}/remove_context", name="message_remove_context", methods={"POST", "DELETE"})
     */
    public function removeContextAction(Request $request, $access, MessageContext $message_context)
    {
        $this->denyAccessUnlessGranted('edit', $message_context->getOwner());

        $em = $this->getDoctrineManager();
        $em->remove($message_context);
        $em->flush();

        if ($this->isRest($access))
            return new JsonResponse(array("status" => "DELETED"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Creates a form to create a Message entity.
     *
     * @param Message $message The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createCreateForm(Message $message)
    {
        if ($message->getBaseType() == "CHECK") {
            $form = $this->createForm(\BisonLab\SakonninBundle\Form\CheckType::class, $message, array(
                'action' => $this->generateUrl('message_create'),
                'method' => 'POST',
            ));
            $form->add('submit', SubmitType::class, array('label' => 'Create'));
        } else {
            $form = $this->createForm(\BisonLab\SakonninBundle\Form\MessageType::class, $message, array(
                'action' => $this->generateUrl('message_create'),
                'method' => 'POST',
            ));
            $form->add('submit', SubmitType::class, array('label' => 'Save'));
        }
        return $form;
    }

    public function createCreatePmForm(Message $entity)
    {
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\PmType::class, $entity, array(
            'action' => $this->generateUrl('pm_create'),
            'method' => 'POST',
        ));
        $form->add('submit', SubmitType::class, array('label' => 'Send'));
        return $form;
    }

    /**
     * Creates an Edit form to create a Message entity.
     *
     * @param MessageType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createEditForm(Message $message)
    {
        if ($message->getBaseType() == "CHECK") {
            $form = $this->createForm(\BisonLab\SakonninBundle\Form\CheckType::class, $message);
        } else {
            $form = $this->createForm(\BisonLab\SakonninBundle\Form\MessageType::class, $message);
        }
        $form->add('submit', SubmitType::class, array('label' => 'Send'));
        return $form;
    }

    /**
     * Creates a form to delete a message entity.
     *
     * @param Message $message The message entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createDeleteForm(Message $message, $access = "ajax")
    {
        $form_name = "message_delete_" . $message->getId();
        return $this->get('form.factory')->createNamedBuilder($form_name,FormType::class)
            ->setAction($this->generateUrl('message_delete', array(
                'id' => $message->getId(),
                'access' => $access)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
