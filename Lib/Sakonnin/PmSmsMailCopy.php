<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 *
 */

class PmSmsMailCopy
{
    use CommonFunctions;

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($number = $receiver->getMobilePhoneNumber())
                $this->sendSms($message, $number, $options);
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
            $this->sendNotification($receiver, $message->getBody(), array('message_type' => 'PM'));
        }
    }
}
