<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 *
 */

class MailCopy
{
    use CommonFunctions;

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $options['provide_link'] = false;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
    }
}
