<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class ForwardOnErrorSubject
{
    use CommonFunctions;

    /* You may call this lazyness, just having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];

        // First, check subject, no error, return.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom())
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
        // Message is handled, put in the archive
        $message->setState('ARCHIVED');
    }
}
