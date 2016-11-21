<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class MailForward
{
    use CommonFunctions;

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = isset($options['attributes']) ? $options['attributes'] : array();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            $this->sendMail($message, $receiver, $options);
        }
    }
}
