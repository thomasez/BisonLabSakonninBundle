<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class ForwardOnErrorSubject
{
    use CommonFunctions;

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

        $receivers = isset($options['attributes']) ? $options['attributes'] : array();
        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom() && preg_match("/\w+@\w+/", $first->getFrom()))
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            $this->sendMail($message, $receiver, $options);
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
