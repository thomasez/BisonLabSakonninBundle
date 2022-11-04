<?php

namespace BisonLab\SakonninBundle\Service;
use Symfony\Component\HttpFoundation\Request;

/*
 * Handling ways to send and receive SMSes.
 * I could extend this to use external/custom thignies, but I'd rather make you
 * commit the method to this system :=)
 */

class SmsHandler
{
    protected $container;
    protected $sender_class;
    protected $receiver_class;
    protected $options;

    // This is easy, anyone can call it and it has a simple interface.
    public $senders = array(
        'dummy' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\SmsHandler\Dummy',
            'description' => "SMSes goes nowhere",
        ),
        'pswincom_mail' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\SmsHandler\PsWinComMail',
            'description' => "SMS via pswin.com old mailinterface",
        ),
        'pswincom_post' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\SmsHandler\PsWinComPost',
            'description' => "SMS via pswin.com old webservice like thingie",
        )
    );

    // Receiving on the other hand, is done how?
    // Thought about sending the request object, but no. The Controller
    // compiles as much data as possible and sends that.
    public $receivers = array(
        'pswincom_post' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\SmsHandler\PsWinComPost',
            'description' => "SMS via pswin.com old webservice like thingie",
        ),
        'dummy' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\SmsHandler\Dummy',
            'description' => "SMSes goes nowhere",
        )
    );

    public function __construct($container, $options = array())
    {
        $this->container = $container;
        $this->options = $options;
        if (isset($options['sender'])) {
            if (!isset($this->senders[$options['sender']]))
                throw new \InvalidArgumentException("The SMS sender specified does not exist.");
            $this->sender_class = $this->senders[$options['sender']]['class'];
        }
        if (isset($options['receiver'])) {
            if (!isset($this->receivers[$options['receiver']]))
                throw new \InvalidArgumentException("The SMS receiver specified does not exist.");
            $this->receiver_class = $this->receivers[$options['receiver']]['class'];
        }
    }

    public function send($message, $receivers)
    {
        if ($this->sender_class) {
            $this->sender = new $this->sender_class($this->container, $this->options);
            return $this->sender->send($message, $receivers);;
        } else {
            throw new \InvalidArgumentException("Cannot send SMS because no method is set");
        }
    }

    /*
     * 
     */
    public function receive($data)
    {
        if ($this->receiver_class) {
            $this->receiver = new $this->receiver_class($this->container, $this->options);
            return $this->receiver->receive($data);;
        } else {
            throw new \InvalidArgumentException("Cannot handle SMS reception because no method is set");
        }
    }
}
