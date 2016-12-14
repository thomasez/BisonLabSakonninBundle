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
        foreach ($receivers as $receiver) {
            $this->sendSms($message, $receiver, $options);
        }
    }
}
