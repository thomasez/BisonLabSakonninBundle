<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

/*
 * Send a PM to everyone.
 */

class Broadcast
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'broadcast' => [
            'description' => "Send PM to all enabled users",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ],
    ];

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
