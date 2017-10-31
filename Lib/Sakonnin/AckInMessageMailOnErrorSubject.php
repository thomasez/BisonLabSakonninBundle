<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\Message;

/*
 */

class AckInMessageMailOnErrorSubject
{
    use CommonFunctions;

    protected $container;
    protected $router;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function execute($options = array())
    {
        $message = $options['message'];

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom() && preg_match("/\w+@\w+/", $first->getFrom()))
            $receivers[] = $first->getFrom();

        $pm = 'You got a message\nSubject:' . $message->getSubject();

        $router = $this->getRouter();
        $url = $router->generate('message_show',
            array('id' => $message->getId()), true);
        $pm .= "Link to the message: " . $url  . "\n\n";

        foreach ($receivers as $receiver) {
            $this->sendPm($pm, $receiver, $options);
        }

        // Then, check subject, no error, no mail.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            $this->sendMail($message, $addr, $options);
        }
    }

    public function getRouter()
    {
        if (!$this->router) {
            $this->router = $this->container->get('router');
        }
        return $this->router;
    }

}
