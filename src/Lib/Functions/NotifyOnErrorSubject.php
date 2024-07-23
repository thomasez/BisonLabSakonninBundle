<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

/*
 */

class NotifyOnErrorSubject
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
    ];

    /* You may call this lazyness, just having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];

        // First, check subject, no error, return.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) ? $options['attributes'] : array();
        // I'm not ready for validating a mail address. this is just a simple.
        // TODO: Find out where this came from and decide who/what til get it.
        // This is most probably wrong and the From is a username.
        if ($first->getFrom())
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            $this->sendNotification($receiver, $message->getBody(), [
                'original_message' => $message,
                ]);
        }

        return true;
    }
}
