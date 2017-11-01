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
        $userManager = $this->container->get('fos_user.user_manager');
        
        foreach ($userManager->findUsers() as $receiver) {
            if ($receiver->getEnabled())
                $this->sendNotification($receiver, $message->getBody(),
                    array('message_type' => 'PM'));
        }
    }
}
