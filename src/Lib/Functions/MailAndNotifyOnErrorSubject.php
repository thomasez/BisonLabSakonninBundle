<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

class MailAndNotifyOnErrorSubject
{
    use CommonFunctions;

    public $callback_functions = [
        'MailAndNotifyOnErrorSubject' => array(
            'description' => "Send mail and notification to user when the subject has the word error in it.",
            'attribute_spec' => "Username",
            'needs_attributes' => true,
        ),
    ];

    public $forward_functions = [
    ];

    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router
    ) {
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
        if ($first->getFrom())
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);

             $this->sendNotification($receiver, $message->getBody(), [
                 'original_message' => $message,
                 ]);
        }
    }
}
