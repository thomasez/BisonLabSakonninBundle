<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use BisonLab\SakonninBundle\Service\SmsHandler;

/*
 *
 */
class SmsCopy
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'smscopy' => array(
            'description' => "Send copy of message as SMS.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
    ];

    public function __construct(
        private SmsHandler $smsHandler,
    ) {
    }

    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $options['provide_link'] = true;
        $sms_numbers = [];
        foreach ($receivers as $receiver) {
            if ($number = $receiver->getMobilePhoneNumber())
                $sms_numbers[] = $number;
        }
        $this->sendSms($message, $sms_numbers, $options);
    }
}
