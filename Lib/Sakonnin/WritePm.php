<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class WritePm
{
    use CommonFunctions;

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        return true;
        $message = $options['message'];
        $receivers = isset($options['attributes']) ? $options['attributes'] : array();
        foreach ($receivers as $receiver) {
            $this->sendPm($message, $receiver, $options);
        }

    }
}
