<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Service\Messages as SakonninMessages;

/**
 * Message controller.
 */
#[Route(path: '/{access}/sakonnin_message', defaults: ['access' => 'web'], requirements: ['access' => 'web|rest|ajax'])]
class MessageController extends AbstractController
{
    use \BisonLab\CommonBundle\Controller\CommonControllerTrait;
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected SakonninMessages $sakonninMessages,
        protected SerializerInterface $serializer
    ) {
    }

    /**
     * Lists all Message entities.
     *
     * don't like this. Using just "/" which would be logical makes the
     * routecomponent match every GET below this one.
     * Another option is to put this one as the last one.
     */
    #[Route(path: '/list', name: 'messages_list', methods: ['GET'])]
    public function listAction(Request $request, $access)
    {
        $this->denyAccessUnlessGranted('index', new Message());
        
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
            $messages = $this->sakonninMessages->getMessages($criterias);
        
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
     */
    #[Route(path: '/me', name: 'message', methods: ['GET'])]
    public function myMessagesAction(Request $request, $access)
    {
        // Todo: paging or just show the last 20
        $messages = $this->sakonninMessages->getMessagesForLoggedIn(array('not_message_type' => 'PM'));
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Lists all Message entities.
     */
    #[Route(path: '/unread', name: 'message_unread')]
    public function unreadAction(Request $request, $access)
    {
        $messages = $this->sakonninMessages->getMessagesForLoggedIn(array('state' => 'UNREAD'));
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, [
                'html' =>'@BisonLabSakonnin/Message/_rest_index.html.twig',
            ]);
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig', [
            'entities' => $messages,
            ]);
    }

    /**
     * Lists all Message entities.
     */
    #[Route(path: '/pm', name: 'pm_list', methods: ['GET'])]
    public function pmAction(Request $request, $access)
    {
        $messages = $this->sakonninMessages->getMessagesForLoggedIn(array('message_type' => 'PM'));
        $unread_starts_at = null;
        foreach ($messages as $message) {
            if ($message->getState() == "UNREAD") {
                $message->setState('READ');
                $unread_starts_at = $message->getMessageId();
            }
        }
        $entityManager = $this->getDoctrineManager();
        $entityManager->flush();
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
     */
    #[Route(path: '/messagetype/{id}', name: 'message_messagetype', methods: ['GET'])]
    public function listByTypeAction(Request $request, $access,
        #[MapEntity(expr: 'repository.findOneByIdOrName(id)')] MessageType $messagetype): Response
    {
        $messages = $messageType->getMessages(true);
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_index.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Finds and displays a Message.
     */
    #[Route(path: '/{message_id}', name: 'message_show', methods: ['GET'], requirements: ['message_id' => '\w{13}'])]
    public function showAction(Request $request, $access,
        #[MapEntity(expr: 'repository.findOneByIdOrMessageId(message_id)')] Message $message): Response
    {
        $entityManager = $this->getDoctrineManager();
        $this->denyAccessUnlessGranted('show', $message);
        // If it's shown to receiver, it's read.
        $user = $this->getUser();
        // Not sure I want to set READ automatically. But UNREAD/READ is not
        // archived, which the users should set themselves.
        if ($message->getTo() == $user->getId()) {
            $message->setState("READ");
            $entityManager->flush();
        }
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $messages, array('html' =>'@BisonLabSakonnin/Message/_show.html.twig'));
        }
        return $this->render('@BisonLabSakonnin/Message/show.html.twig',
            array('entity' => $message));
    }

    /**
     * Displays a form to edit an existing message.
     */
    #[Route(path: '/{message_id}/edit', name: 'message_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, $access,
        #[MapEntity(expr: 'repository.findOneByIdOrMessageId(message_id)')] Message $message): Response
    {
        $this->denyAccessUnlessGranted('edit', $message);
        $action = $this->generateUrl('message_edit', array(
            'message_id' => $message->getMessageId(),
            'reload_after_post' => $request->get('reload_after_post'),
            'no_subject' => $request->get('no_subject'),
            'with_expire' => $request->get('with_expire'),
            'full_edit' => $request->get('full_edit'),
            'access' => $access
            ));
        if ($request->get('full_edit')) {
            $editForm = $this
               ->createForm('BisonLab\SakonninBundle\Form\MessageType', $message, [
                    'action' => $action,
                    ]);
        } else {
            $editForm = $this
               ->createForm('BisonLab\SakonninBundle\Form\EditMessageType', $message, [
                    'action' => $action,
                    'with_expire' => $request->get('with_expire'),
                    'no_subject' => $request->get('no_subject'),
                    ]);
        }
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            if ($editForm->isValid()) {
                $this->getDoctrineManager()->flush();

                if ($this->isRest($access)) {
                    return new JsonResponse([
                        "status" => "OK",
                        "message" => $message->__toArray()
                        ], 200);
                }
                return $this->redirectToRoute('message_show',
                    array('access' => $access, 'message_id' => $message->getMessageId()));
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
     * Displays a form to edit an existing message.
     */
    #[Route(path: '/{message_id}/state/{state}', name: 'message_state', methods: ['POST'])]
    public function stateAction(Request $request, $access, $state,
        #[MapEntity(expr: 'repository.findOneByIdOrMessageId(message_id)')] Message $message): Response
    {
        $this->denyAccessUnlessGranted('edit', $message);
        $message->setState($state);
        $this->getDoctrineManager()->flush();

        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK",
                'state' => $message->getState()),
                Response::HTTP_OK);
        }
        return $this->redirectToRoute('message_show',
            array('access' => $access, 'message_id' => $message->getMessageId()));
    }

    /**
     * Lists all Messages with that context, filtered if requested.
     */
    #[Route(path: '/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}', name: 'message_context_search', methods: ['GET'])]
    public function searchContextGetAction(Request $request, $access, $system, $object_name, $external_id)
    {
        // Search/Index - basically same same. For now at least.
        $this->denyAccessUnlessGranted('index', new Message());
        $criterias = $request->get('criterias') ?? [];
        $criterias['context'] = [
            'system'      => $system,
            'object_name' => $object_name,
            'external_id' => $external_id,
            ];
        $messages = [];
        foreach ($this->sakonninMessages->getMessagesForContext($criterias) as $m) {
            if ($this->isGranted('show', $m))
                $messages[] = $m;
        }

        // If it's REST, but HTML, we'll be returning HTML content, but not a
        // complete page.
        if ($this->isRest($access)) {
            if (count($messages) == 1) {
                return $this->render('@BisonLabSakonnin/Message/_show.html.twig',
                    array('entity' => $messages[0]));
            } else {
                return $this->render('@BisonLabSakonnin/Message/_index.html.twig',
                    array('entities' => $messages));
            }
        } else {
            if (count($messages) == 1) {
                return $this->render('@BisonLabSakonnin/Message/show.html.twig',
                    array('entity' => $messages[0]));
            } else {
                return $this->render('@BisonLabSakonnin/Message/index.html.twig',
                    array('entities' => $messages));
            }
        }
    }

    /**
     * Creates a new PM
     * (Which does look more and more like the usual createMessage.)
     */
    #[Route(path: '/pm', name: 'pm_create', methods: ['POST'])]
    public function createPmAction(Request $request, $access)
    {
        $data = $request->request->all();
        $form = $this->sakonninMessages->getCreatePmForm($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
            $this->denyAccessUnlessGranted('create', $message);
            $entityManager = $this->getDoctrineManager();
            $user = $this->getUser();

            $message_type = $data['message_type'] ?: "PM";
            $message->setMessageType(
                $entityManager->getRepository(MessageType::class)
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
                if (!$irt_msg = $entityManager->getRepository(Message::class)->findOneBy(array('message_id' => (string)$irt)))
                    throw $this->createAccessDeniedException('No or bad reply .');
                if ($irt_msg->getTo() != $user->getId())
                    throw $this->createAccessDeniedException('No or bad reply .');
                $message->setInReplyTo($irt_msg);
            }
            $message->setFromType('INTERNAL');
            $message->setFrom($user->getId());

            $this->sakonninMessages->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, "OK Done");
            }
            return $this->redirectToRoute('message_show',
                array('access' => $access, 'message_id' => $message->getMessageId()));
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
     */
    #[Route(path: '/create', name: 'message_create', methods: ['POST'])]
    public function createAction(Request $request, $access)
    {
        $entityManager = $this->getDoctrineManager();
        if ($parsed = json_decode($request->getContent(), true)) {
            $data = $parsed['message_data'];
            if (!isset($data['from_type'])) {
                $data['from_type'] = "EXTERNAL";
            }
            // Gotta do some security check. This is a hack, but it should
            // work..
            if (isset($data['message_type']) && $message_type = $entityManager->getRepository(MessageType::class)->findOneByName($data['message_type'])) {
                $message = new Message();
                $message->setMessageType($message_type);
                $this->denyAccessUnlessGranted('create', $message);
            } else {
                // No messaagetype? naaah.
                throw $this->createAccessDeniedException('No access, no message type found for message.');
            }
            $message = $this->sakonninMessages->postMessage($data, isset($parsed['message_context']) ? $parsed['message_context'] : array());
            if ($message) {
                return $this->returnRestData($request, $message->__toArray(), null, 204);
            }
            // This is so the wrong error to return.
            return $this->returnErrorResponse("Validation Error", 400);
        }

        $data = $request->request->all();
        if (!$form = $this->sakonninMessages->getCreateForm($data))
            return $this->returnErrorResponse("Message create Error, Bad data", 400);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ok, it's valid. We'll send this to postMessage then.
            $message = $form->getData();
            if (!$message->getMessageType() && isset($data['message_type'])) {
                $entityManager = $this->getDoctrineManager();
                $message->setMessageType(
                    $entityManager->getRepository(MessageType::class)
                        ->findOneByName($data['message_type'])
                );
            }
            $this->denyAccessUnlessGranted('create', $message);
            if (!$message->getFromType()) {
                if (isset($data['from_type']))
                    $message->setFromType($data['from_type']);
                else
                    $message->setFromType("EXTERNAL");
            }

            $this->sakonninMessages->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, $message->__toArray(), null, 204);
            }
            return $this->redirectToRoute('message_show',
                array('access' => $access, 'message_id' => $message->getMessageId()));
        }

        if ($this->isRest($access)) {
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('@BisonLabSakonnin/Message/new.html.twig',
            array('entity' => $message, 'form'   => $form->createView()));
    }

    /**
     * Deletes a message.
     */
    #[Route(path: '/{message_id}', name: 'message_delete', methods: ['DELETE'], requirements: ['message_id' => '\w{13}'])]
    public function deleteAction(Request $request, $access,
        #[MapEntity(expr: 'repository.findOneByIdOrMessageId(message_id)')] Message $message): Response
    {
        $this->denyAccessUnlessGranted('delete', $message);
        $form = $this->createDeleteForm($message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrineManager();
            $entityManager->remove($message);
            $entityManager->flush();
            if ($this->isRest($access))
                return new JsonResponse(array("status" => "DELETED"),
                    Response::HTTP_OK);
        }
        if ($this->isRest($access))
            return $this->returnErrorResponse("Validation Error",
                Response::HTTP_BAD_REQUEST,
                $this->handleFormErrors($form));
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Does stuff on a list of messages.
     */
    #[Route(path: '/messages', name: 'message_messages', methods: ['POST', 'DELETE'])]
    public function messsagesAction(Request $request, $access)
    {
        if (!$this->isCsrfTokenValid('message-messages', $request->request->get('_token')))
            return $this->redirect($request->headers->get('referer'));

        $msglist = $request->request->all('message_list');
        $submit = $request->request->get('submit');
        if (!is_array($msglist))
             return $this->redirect($request->headers->get('referer'));
        $entityManager = $this->getDoctrineManager();
        foreach ($msglist as $msgid) {
            if (!$message = $this->_getMessage($msgid))
                continue;
            if ($submit == "Delete") {
                if (!$this->isGranted('delete', $message))
                    return $this->redirect($request->headers->get('referer'));
                $entityManager->remove($message);
                $entityManager->flush();
            }
            if ($submit == "Archive" && $message->isArchiveable()) {
                // To be honest, archiving is more like delete than edit.
                // TODO: Add an "archive" security attribute
                if (!$this->isGranted('delete', $message))
                    return $this->redirect($request->headers->get('referer'));
                $message->setState("ARCHIVED");
                $entityManager->flush();
            }
        }

        if ($this->isRest($access))
            return new JsonResponse(array("status" => "DONE"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Check for unread messages
     */
    #[Route(path: '/check_unread', name: 'check_unread', methods: ['GET'])]
    public function checkUnreadAction(Request $request, $access)
    {
        $messages = $this->sakonninMessages
            ->getMessagesForLoggedIn(array('state' => 'UNREAD'));

        if ($messages) {
            return $this->returnRestData($request, array('amount' => count($messages)));
        }
        return $this->returnRestData($request, array('amount' => 0));
    }

    /**
     * Creates a new message.
     */
    #[Route(path: '/new', name: 'message_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, $access)
    {
        $message = new Message();
        if ($message_type = $request->get('message_type')) {
            $em = $this->getDoctrineManager();
            if (is_numeric($message_type)) {
                $message->setMessageType(
                    $em->getRepository(MessageType::class)
                        ->find($message_type)
                );
            } else {
                $message->setMessageType(
                    $em->getRepository(MessageType::class)
                        ->findOneByName($message_type)
                );
            }
        }

        $form = $this->createForm('BisonLab\SakonninBundle\Form\MessageType',
            $message);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($context_data = $request->get('message_context')) {
                    $message_context = new MessageContext($context_data);
                    $message->addContext($message_context);
                }
                $this->sakonninMessages->postMessage($message);
                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK", 200));
                }
                return $this->redirectToRoute('message_show',
                    array('access' => $access, 'message_id' => $message->getMessageId()));
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
     */
    #[Route(path: '/{message_id}/add_context', name: 'message_add_context', methods: ['POST'])]
    public function addContextAction(Request $request, $access,
        #[MapEntity(expr: 'repository.findOneByIdOrMessageId(message_id)')] Message $message): Response
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
        $entityManager = $this->getDoctrineManager();
        $entityManager->persist($context);
        $entityManager->flush();

        if ($this->isRest($access))
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Remove just the context
     */
    #[Route(path: '/{id:message_contect}/remove_context', name: 'message_remove_context', methods: ['POST', 'DELETE'])]
    public function removeContextAction(Request $request, $access, MessageContext $message_context)
    {
        // Is "delete" more correct? Should I just do thee check directly on
        // the context?
        $this->denyAccessUnlessGranted('edit', $message_context->getOwner());

        $entityManager = $this->getDoctrineManager();
        $entityManager->remove($message_context);
        $entityManager->flush();

        if ($this->isRest($access))
            return new JsonResponse(array("status" => "DELETED"),
                Response::HTTP_OK);
        else
            return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Creates a form to create a Message.
     *
     * @param Message $message
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
        } elseif ($message->getBaseType() == "NOTE") {
            $form = $this->createForm(\BisonLab\SakonninBundle\Form\MessageType::class, $message, array(
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

    /**
     * Creates an Edit form to create a Message.
     *
     * @param MessageType $entity
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
     * Creates a form to delete a message.
     *
     * @param Message $message The message
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createDeleteForm(Message $message, $access = "ajax")
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('message_delete', array(
                'message_id' => $message->getMessageId(),
                'access' => $access)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
