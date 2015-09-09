<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class MailForward
{

    protected $container;
    protected $router;

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

        $router = $this->getRouter();
        $url = $router->generate('message_show', array('id' => $message->getId()), true);
        // Not gonna do html, yet at least..
        // $body = '<a href="' . $url . '">Link to this message</a>' . "\n\n";
        $body = "Link to this message: " . $url  . "\n\n";
        $body .= $message->getBody();

        $mail = \Swift_Message::newInstance()
        ->setSubject($message->getSubject())
        ->setFrom($message->getFrom())
        ->setTo(implode(",", $options['attributes']))
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
