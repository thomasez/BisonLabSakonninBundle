<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class SendPmToUserList
{
    use CommonFunctions;

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = isset($options['attributes']) ? $options['attributes'] : array();
        foreach ($receivers as $receiver) {
            $this->sendPm($message, $receiver, $options);
        }
    }
}
