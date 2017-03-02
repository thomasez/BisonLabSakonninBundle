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

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Message controller.
 *
 * @Route("/{access}/message", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
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
     * @Route("/me", * name="message")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        $messages = $sm->getMessagesForLoggedIn();
        return $this->render('BisonLabSakonninBundle:Message:index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Lists all Message entities.
     *
     * @Route("/unread", name="message_unread")
     */
    public function unreadAction(Request $request, $access)
    {
        $em = $this->getDoctrineManager();
        $sm = $this->container->get('sakonnin.messages');
        $messages = $sm->getMessagesForLoggedIn('UNREAD');
        return $this->render('BisonLabSakonninBundle:Message:index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Finds and displays a Message entity.
     *
     * @Route("/{id}", name="message_show")
     * @Method("GET")
     */
    public function showAction(Request $request, $access, $id)
    {
        $em = $this->getDoctrineManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:Message')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Message entity.');
        }
        $this->denyAccessUnlessGranted('show', $entity);
        // If it's shown to receiver, it's read.
        $sm = $this->container->get('sakonnin.messages');
        $user = $this->getUser();
        if ($entity->getTo() == $user->getId()) {
            $entity->setState("READ");
            $em->flush();
        }
        return $this->render('BisonLabSakonninBundle:Message:show.html.twig',
            array('entity' => $entity));
    }

    /**
     * Lists all Messages with that context.
     *
     * @Route("/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}", name="message_context_search")
     * @Method("GET")
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
            $conf['show_template'] = "BisonLabSakonninBundle:Message:_show.html.twig";
            $conf['list_template'] = "BisonLabSakonninBundle:Message:_index.html.twig";
        } else {
            $conf['show_template'] = "BisonLabSakonninBundle:Message:show.html.twig";
            $conf['list_template'] = "BisonLabSakonninBundle:Message:index.html.twig";
        }
        return $this->contextGetAction(
            $request, $conf, $access, $system, $object_name, $external_id);
    }

    /**
     * Creates a new PM
     * (Which does look more and more like the usual createMessage.)
     *
     * @Route("/pm", name="pm_create")
     * @Method("POST")
     */
    public function createPmAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');

        $data = $request->request->all();
        $form = $sm->getCreatePmForm($data);
        $this->handleForm($form, $request, $access);

        if ($form->isValid()) {
            $message = $form->getData();
            $em = $this->getDoctrineManager();

            $message_type = $data['message_type'] ?: "PM";
            $message->setMessageType(
                $em->getRepository('BisonLabSakonninBundle:MessageType')
                    ->findOneByName($message_type)
            );

            $message->setToType('INTERNAL');
            if ($data['to_userid']) {
                $message->setTo($data['to_userid']);
            }

            $user = $this->getUser();
            $message->setFromType('INTERNAL');
            $message->setFrom($user->getId());

            $sm->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, "OK Done");
            }
            return $this->redirect($this->generateUrl('message_show',
                array('id' => $message->getId())));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('BisonLabSakonninBundle:Message:new.html.twig',
            array('entity' => $message, 'form'   => $form->createView()
            ));
    }

    /**
     * Creates a new Message
     *
     * @Route("/", name="message_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        if ($data = json_decode($request->getContent(), true)) {
            if (!isset($data['message_data']['from_type'])) {
                $data['message_data']['from_type'] = "EXTERNAL";
            }
            $message = $sm->postMessage($data['message_data'], isset($data['message_context']) ? $data['message_context'] : array());
            if ($message) {
                return $this->returnRestData($request, $message->__toArray(), null, 204);
            }
            return $this->returnErrorResponse("Validation Error", 400);
        }

        $data = $request->request->all();
        $form = $sm->getCreateForm($data);
        $this->handleForm($form, $request, $access);

        if ($form->isValid()) {
            // Ok, it's valid. We'll send this to postMessage then.
            $message = $form->getData();
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
            return $this->redirect($this->generateUrl('message_show',
                    array('id' => $message->getId())));
        }

        if ($this->isRest($access)) {
            return $this->returnErrorResponse("Validation Error", 400,
                $this->handleFormErrors($form));
        }

        return $this->render('BisonLabSakonninBundle:Message:new.html.twig',
            array('entity' => $message, 'form'   => $form->createView()));
    }

    /**
     * Check for unread messages
     *
     * @Route("/check_unread/", name="check_unread")
     * @Method("GET")
     */
    public function checkUnreadAction(Request $request, $access)
    {
        $em = $this->getDoctrineManager();
        $sm = $this->container->get('sakonnin.messages');
        $user = $this->getUser();

        $repo = $em->getRepository('BisonLabSakonninBundle:Message');
        $messages = $repo->createQueryBuilder('m')
            ->where("m.state = 'UNREAD'")
            ->andWhere('m.to = :userid')
            ->setParameter('userid', $user->getId())
            ->getQuery()->getResult();
        if ($messages) {
            return $this->returnRestData($request, array('amount' => count($messages)));
        }
        // return $this->returnRestData($request, false);
        return $this->returnRestData($request, array('amount' => 0));
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
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\MessageType::class, $entity, array(
            'action' => $this->generateUrl('message_create'),
            'method' => 'POST',
        ));
        $form->add('submit', SubmitType::class, array('label' => 'Send'));
        return $form;
    }

    public function createCreatePmForm(Message $entity)
    {
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\PmType::class, $entity, array(
            'action' => $this->generateUrl('pm_create'),
            'method' => 'POST',
        ));
        $form->add('message_type', HiddenType::class);
        $form->add('submit', SubmitType::class, array('label' => 'Send'));
        return $form;
    }
}
