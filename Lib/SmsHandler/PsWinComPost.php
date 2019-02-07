<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

use Symfony\Component\HttpFoundation\Response;
use BisonLab\SakonninBundle\Entity\MessageType;

/*
 * The simplest way to send an SMS via pswin.com. Hopefully not lasting for
 * long. It's nicked from the old CrewCall and does not look good at all.
 */

class PsWinComPost
{
    use \BisonLab\SakonninBundle\Lib\Sakonnin\CommonFunctions;

    protected $container;
    protected $username;
    protected $password;
    protected $mailaddress;
    protected $sender;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
        // Let it just barf if the parameters are  missing.
        $this->username = $options['username'];
        $this->password = $options['password'];
        $this->smsfrom  = $options['smsfrom'];
        $this->mailfrom = $options['mailfrom'];
        $this->mailto   = $options['mailto'];
        $this->default_country_prefix   = $options['default_country_prefix'];
        $this->national_number_lenght   = $options['national_number_lenght'];
    }

    public function receive($data)
    {
$logger = $this->container->get('logger');
        // Sender is a phone number. I wonder if I should do an attempt at
        // finding a user based on it, but to me honest I think I'll make the
        // "Callback function" handle that part.

        // Sanitize it.
        $from = preg_replace("/\D/", "", $data['SND']);
        if (strlen($from) < $this->national_number_lenght)
            return ['message' => 'ERROR', 'errcode' => Response::HTTP_FORBIDDEN];

        $body = $data['TXT'];
        $sm = $this->container->get('sakonnin.messages');
        $message = [];
        $message['from'] = $from;
        $message['from_type'] = "SMS";
        $message['body'] = urldecode($body);

        // Could be discussing if setting message type here is correct, but
        // something has to be set.
        $message['message_type'] = $data['messaage_type'] ?? "SMS";
        $sm->postMessage($message);

        return true;
    }
}
