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

        foreach ($receivers as $receiver) {
            if ($number = $receiver->getMobilePhoneNumber())
                $this->sendSms($message, $number, $options);
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
    }
}
