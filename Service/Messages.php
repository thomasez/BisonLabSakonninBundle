<?php

namespace BisonLab\SakonninBundle\Service;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Controller\MessageController;
use BisonLab\SakonninBundle\Form\MessageType as MessageForm;

/**
 * Messages service.
 */
class Messages
{

    private $container;
    private $entityManager;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function postMessage($data, $context_data = array())
    {
        $em = $this->_getManager();
        $message = null;
        if ($data instanceof Message) {
            $message = $data;
        } else {
            $message = new Message($data);

            $em = $this->_getManager();
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
        }

        if (!$message->getFrom())
            $message->setFrom($this->_getFromFromUser());

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
        $em = $this->_getManager();
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

        if (!$message->getFrom())
            $message->setFrom($this->_getFromFromUser());

        $c = new MessageController();
        $c->setContainer($this->container);

        $form = $c->createForm(new MessageForm(), $message);
        $form->add('submit', 'submit', array('label' => 'Send'));

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


    private function _getManager()
    {
        if (!$this->entityManager) {
            $this->entityManager 
                = $this->container->get('doctrine')->getManager();
        }
        return $this->entityManager;
    } 
}
