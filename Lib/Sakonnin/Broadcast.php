<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class Broadcast
{
    use CommonFunctions;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    /* You may call this lazyness, just having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $userManager = $this->container->get('fos_user.user_manager');
        
        foreach ($userManager->findUsers() as $receiver) {
            if ($receiver->getEnabled())
                $this->sendPm($message, $receiver, $options);
        }
    }
}
