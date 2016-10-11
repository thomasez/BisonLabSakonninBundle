<?php

namespace BisonLab\SakonninBundle\Service;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Controller\MessageController;

/**
 * Messages service.
 */
class Messages
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function postMessage($data, $context_data = array())
    {
        $em = $this->getDoctrineManager();
        $message = null;
        if ($data instanceof Message) {
            $message = $data;
        } else {
            $message = new Message($data);
            if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                    $message->setMessageType($message_type);            
            } else {
                throw new \InvalidArgumentException("No message type found or set.");
            }

            if (isset($context_data)
                && isset($context_data['system'])
                && isset($context_data['object_name'])
                && isset($context_data['external_id'])) {

                $message_context = new MessageContext($context_data);
                $message->addContext($message_context);
                $em->persist($message_context);
            }

            if (isset($data['in_reply_to'])) {
                if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $data['in_reply_to']))) {
                    return false;
                } else {
                    $message->setInReplyTo($reply_to);
                }
            }
            if (isset($data['from_type'])) {
                $message->setFromType($data['from_type']);
            } else {
                throw new \InvalidArgumentException("No from address type found or set.");
            }

            if (isset($data['to_type']))
                $message->setToType($data['to_type']);
        }

        if (!$message->getFrom()) {
            $message->setFrom($this->_getFromFromUser());
            $message->setFromType("INTERNAL");
        }

        $em->persist($message);
        $em->flush();

        // I planned to use an event listener to dispatch callback/forward
        // functions, but why? This postMessage functions shall be the only
        // entry point for creating messages, so why should I bother?
        $dispatcher = $this->container->get('sakonnin.functions');
        $dispatcher->dispatchMessageFunctions($message);

        return $message;
    }

    public function getCreateForm($options = array())
    {
        $em = $this->getDoctrineManager();
        $message = null;
        $message_context = null;
        if (isset($options['message']) && $options['message'] instanceof Message) {
             $message =  $options['message'];
        } elseif (isset($options['message_data']) && $data = $options['message_data']) {
            if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                $data['message_type'] = $message_type;
            }
            $message = new Message($data);
        } else {
            $message = new Message();
        }

        if (isset($options['message_context'])) {
            $message_context = new MessageContext($options['message_context']);
            $message->addContext($message_context);
        }

        // What does the form say?
        if (isset($options['message_data']['in_reply_to'])) {
            if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $options['message_data']['in_reply_to']))) {
                return false;
            } else {
                $message->setInReplyTo($reply_to);
            }
        }

        if (!$message->getFrom())
            $message->setFrom($this->_getFromFromUser());

        $c = new MessageController();
        $c->setContainer($this->container);

        $form = $c->createCreateForm($message);

        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    /* Jadajada, I just had to use that function name..*/
    private function _getFromFromUser() {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($user && method_exists($user, 'getEmail') && !empty($user->getEmail()))
            return $user->getEmail();
        elseif ($user && method_exists($user, 'getName'))
            return $user->getName();
        elseif ($user && method_exists($user, 'getUserName'))
            return $user->getUserName();
        else
            return '';
    }
}
