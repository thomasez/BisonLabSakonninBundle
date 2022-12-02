<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

/*
 */

class MailForward
{
    use CommonFunctions;

    public $callback_functions = [
        'mailforward' => array(
            'description' => "Forward to mail adress(es) in attributes list.",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
    ];

    public $forward_functions = [
        'mailforward' => array(
            'description' => "Send copy to mail adress(es) in attributes list",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
    ];

    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router
    ) {
    }

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = isset($options['attributes']) ? $options['attributes'] : array();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
        // Message is handled, put in the archive
        $message->setState('ARCHIVED');
    }
}
