<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use BisonLab\SakonninBundle\Entity\Message;
use Symfony\Component\Routing\RouterInterface;

/*
 */

class NotifyiOnReceptionMailOnErrorSubject
{
    use CommonFunctions;

    public $callback_functions = [
        'NotifyiOnReceptionMailOnErrorSubject' => array(
            'description' => "Send a Notification when message received. If ERROR in subject, send mail aswell.",
            'attribute_spec' => "Username",
            'needs_attributes' => false,
        ),
    ];

    public $forward_functions = [
    ];

    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function execute($options = array())
    {
        $message = $options['message'];

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        $receivers[] = $first->getFrom();

        $pm = 'You got a message<br/>Subject: ' . $message->getSubject();

        $url = $this->router->generate('message_show',
            array('message_id' => $message->getMessageId()), true);
        $pm .= '<br/><a href="' . $url . '">Link to the message</a>';

        foreach ($receivers as $receiver) {
            $this->sendNotification($receiver, $pm, array('content_type' => 'text/html'));
        }

        // Then, check subject, no error, no mail.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom())
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
    }
}
