<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 * Send a PM to everyone.
 */

class Broadcast
{
    use CommonFunctions;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function execute($options = array())
    {
        $message = $options['message'];
        $user_repo = $this->getUserRepository();

        foreach ($user_repo->findAll() as $receiver) {
            if ($receiver->getEnabled())
                $this->sendNotification($receiver, $message->getBody(),
                    array('message_type' => 'PM'));
        }
    }
}
