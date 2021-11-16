<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 *
 */

class SmsCopy
{
    use CommonFunctions;

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
