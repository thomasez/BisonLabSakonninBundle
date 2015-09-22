<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class ForwardOnErrorSubject
{

    protected $container;
    protected $router;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    /* You may call this lazyness, just having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];

        // First, check subject, no error, return.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        // Find who to send this to.
        $first = $message->getFirstPost();

        $forwards = isset($options['attributes']) ? $options['attributes'] : array();
        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom() && preg_match("/\w+@\w+/", $first->getFrom()))
            $forwards[] = $first->getFrom();

        $router = $this->getRouter();
        $url = $router->generate('message_show', array('id' => $message->getId()), true);
        // Not gonna do html, yet at least..
        // $body = '<a href="' . $url . '">Link to this message</a>' . "\n\n";
        $body = "Link to this message: " . $url  . "\n\n";
        $body .= $message->getBody();

        $mail = \Swift_Message::newInstance()
        ->setSubject($message->getSubject())
        ->setFrom($message->getFrom())
        ->setTo(implode(",", $forwards))
        ->setBody($body,
            'text/plain'
        ) ;
        $this->container->get('mailer')->send($mail);

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
