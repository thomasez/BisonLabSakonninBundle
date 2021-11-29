<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 *
 */

class SmsMail
{
    use CommonFunctions;

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
