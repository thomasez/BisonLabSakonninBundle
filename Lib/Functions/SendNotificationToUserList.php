<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

/*
 */

class SendNotificationToUserList
{
    use CommonFunctions;

    public $callback_functions = [
        'sendnotificationtouserlist' => array(
            'description' => "Send a notification to everyone in the attributes list.",
            'attribute_spec' => "Username",
            'needs_attributes' => true,
        ),
    ];

    public $forward_functions = [
    ];

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = isset($options['attributes']) ? $options['attributes'] : array();
        foreach ($receivers as $receiver) {
            $this->sendNotification($receiver, $message->getBody());
        }
    }
}
