<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

/*
 *
 */

class PmCopy
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'pmcopy' => array(
            'description' => "Send copy of message as PM.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
    ];

    /* You may call this lazyness, jkust having an options array, but it's also
     * more future proof. */
    public function execute($options = array())
    {
        $message = $options['message'];
        $receivers = $message->getReceivers();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            $this->sendNotification($receiver, $message->getBody(), array('message_type' => 'PM'));
        }
    }
}
