<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

trait CommonFunctions
{
    protected $container;
    protected $router;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function sendMail($message, $receiver, $options = array())
    {
        $body = '';
        if (isset($options['provide_link'])) {
            $router = $this->getRouter();
            $url = $router->generate('message_show', array('id' => $message->getId()), true);
            $body .= "Link to this message: " . $url  . "\n\n";
        }

        $body .= $message->getBody();
        if (!$from = $message->getFrom()) {
            $sm = $this->container->get('sakonnin.messages');
            $from = $sm->getEmailFromUser();
            $message->setFrom($from);
            $message->setFromType('EMAIL');
        }

        $mail = \Swift_Message::newInstance()
        ->setSubject($message->getSubject())
        ->setFrom($from)
        ->setTo($receiver)
        ->setBody($body,
            'text/plain'
        ) ;
        $this->container->get('mailer')->send($mail);

        return true;
    }

    public function sendPm($message, $receiver)
    {
        // Gotta find username.
        $message->setTo($receiver);
        $message->setToType('INTERNAL');
        $message->setBody($body);
        $this->container->get('sakonnin')->postMessage($message);

        return true;
    }

    public function getRouter()
    {
        if (!$this->router) {
            $this->router = $this->container->get('router');
        }
        return $this->router;
    }
}
