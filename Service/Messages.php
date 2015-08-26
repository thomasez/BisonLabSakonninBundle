<?php

namespace BisonLab\SakonninBundle\Service;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;

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

    public function postMessage($data, $context_data)
    {
        $em = $this->_getManager();
        $message = new Message($data);

        if (isset($context)
            && isset($context['system'])
            && isset($context['object_name'])
            && isset($context['external_id'])) {

            $message_context = new MessageContext();
            $message->addContext($message_context);
            $message_context->setSystem($context['system']);
            $message_context->setObjectName($context['object_name']);
            $message_context->setExternalId($context['external_id']);
            $em->persist($message_context);
        }
dump($message);
        $em->persist($message);
        $em->flush();

        return ($message);
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
