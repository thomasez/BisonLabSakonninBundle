<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 * 
 */

class SmsForward
{
    use CommonFunctions;

    /* You may call this lazyness, just having an options array, but it's also
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
