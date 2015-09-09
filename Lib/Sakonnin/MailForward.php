<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class MailForward
{

    protected $container;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        error_log("Kom meg til funksjonen." . print_r($options['attributes'], true));
        $message = $options['message'];

        $mail = \Swift_Message::newInstance()
        ->setSubject($message->getSubject())
        ->setFrom($message->getFrom())
        ->setTo(implode(",", $options['attributes']))
        ->setBody($message->getBody(),
            'text/plain'
        ) ;
        $this->container->get('mailer')->send($mail);

        return true;
    }

}
