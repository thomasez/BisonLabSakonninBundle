<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use BisonLab\SakonninBundle\Service\SmsHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

/*
 *
 */
class PmSmsMailCopy
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'pmsmsmailcopy' => array(
            'description' => "Send message as PM, SMS and mail",
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

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $options['provide_link'] = true;
        $sms_numbers = [];
        foreach ($receivers as $receiver) {
            if ($number = $receiver->getMobilePhoneNumber())
                $sms_numbers[] = $number;
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
            $this->sendNotification($receiver, $message->getBody(), [
                'message_type' => 'PM',
                'original_message' => $message,
                ]);
        }
        $this->sendSms($message, $sms_numbers, $options);
    }
}
