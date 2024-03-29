<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use BisonLab\SakonninBundle\Service\SmsHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

/*
 *
 */
class SmsMail
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'smsmail' => array(
            'description' => "Send message as SMS and mail",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
    ];

    public function __construct(
        private SmsHandler $smsHandler,
        private MailerInterface $mailer,
        private RouterInterface $router
    ) {
    }

    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $sms_numbers = [];
        foreach ($receivers as $receiver) {
            if ($number = $receiver->getMobilePhoneNumber())
                $sms_numbers[] = $number;
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
        $this->sendSms($message, $sms_numbers, $options);
    }
}
