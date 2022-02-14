<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

/*
 *
 */

class MailCopy
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'mailcopy' => array(
            'description' => "Send copy of message as mail.",
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

        $options['provide_link'] = false;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
    }
}
