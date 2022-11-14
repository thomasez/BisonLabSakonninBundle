<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use BisonLab\SakonninBundle\Entity\MessageType;

/*
 * The simplest way to send an SMS via pswin.com. Hopefully not lasting for
 * long. It's nicked from the old CrewCall and does not look good at all.
 */

class PsWinComPost
{
    use \BisonLab\SakonninBundle\Lib\Functions\CommonFunctions;

    protected $username;
    protected $password;
    protected $mailaddress;
    protected $options;

    public $config = [
        'name' => 'pswincom_post',
        'description' => "SMS via pswin.com old webservice like thingie",
        'sends' => true,
        'receives' => true,
    ];

    public function __construct(
            ParameterBagInterface $parameterBag,
    ) {
        // I don't want to barf here.
        $this->options = $parameterBag->get('sakonnin.sms');
    }

    public function receive($data)
    {
        // Sender is a phone number. I wonder if I should do an attempt at
        // finding a user based on it, but to me honest I think I'll make the
        // "Callback function" handle that part.

        // Sanitize it.
        $from = preg_replace("/\D/", "", $data['SND']);
        if (strlen($from) < $this->options['national_number_lenght'])
            return ['message' => 'ERROR', 'errcode' => Response::HTTP_FORBIDDEN];

        $body = $data['TXT'];
        $message = [];
        $message['from'] = $from;
        $message['from_type'] = "SMS";
        $message['body'] = urldecode($body);

        // Could be discussing if setting message type here is correct, but
        // something has to be set.
        $message['message_type'] = $data['messaage_type'] ?? "SMS";
        $this->sakonninMessages->postMessage($message);

        return true;
    }

    public function send($message, $receivers, $options = array())
    {
        $msg = <<<EOMSG
<?xml version="1.0"?>
<SESSION>
<CLIENT>$this->options['username']</CLIENT>
<PW>$this->options['password']</PW>
<MSGLST>
EOMSG;

        // Make it one.
		if (!is_array($receivers))
            $receivers = array($receivers);

        // First, they default to latin 1
        $message = iconv("UTF-8", "ISO-8859-1", $message);
        // Make the message xml-safe:
        $message = htmlspecialchars($message, ENT_XML1, 'ISO-8859-1');
		
        foreach ($receivers as $number) {
            if (strlen((string)$number) == $this->options['national_number_lenght']) $number = $this->options['default_country_prefix'] . $number;
            $msg .= <<<EOMSG
<MSG>
<TEXT>$message</TEXT>
<RCV>$number</RCV>
<SND>$this->options['smsfrom']</SND>
</MSG>
EOMSG;
        }
		
		$msg .="\n</MSGLST>\n</SESSION>";

        /*
         * This is insanely quick and dirty, but was used by the old system and
         * it may have been for a reason. commets hints that curl was replaced
         * with fsockopen and friends..
         */
        $pswincomsmsgateway = fsockopen ($this->options['sms_server_host'], $this->options['sms_server_port'], $errno, $errstr);
        if (!$pswincomsmsgateway) {
            error_log("SMS Gateway not responding, " . $errno . " " . $errstr);
            return false;
        }
        fputs ($pswincomsmsgateway, $msg); 
        while ( ($response = fgets($pswincomsmsgateway)) != false ) {
              $response = trim($response);
        }
        return true;
    }
}
