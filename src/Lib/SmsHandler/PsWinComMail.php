<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

use Symfony\Component\Mime\Email;

/*
 * The simplest way to send an SMS via pswin.com. Hopefully not lasting for
 * long. It's nicked from the old CrewCall and does not look good at all.
 */

class PsWinComMail
{
    use \BisonLab\SakonninBundle\Lib\Functions\CommonFunctions;

    protected $username;
    protected $password;
    protected $mailaddress;
    protected $sender;

    public $config = [
        'name' => 'pswincom_mail',
        'description' => "SMS via pswin.com old mailinterface",
        'sends' => true,
        'receives' => false,
    ];

    public function __construct($options = array())
    {
        // Cannot barf.
        if (empty($options)) return;

        $this->username = $options['username'];
        $this->password = $options['password'];
        $this->smsfrom  = $options['smsfrom'];
        $this->mailfrom = $options['mailfrom'];
        $this->mailto   = $options['mailto'];
        $this->default_country_prefix   = $options['default_country_prefix'];
        $this->national_number_lenght   = $options['national_number_lenght'];
    }

    public function send($message, $receivers, $options = array())
    {
        $msg = <<<EOMSG
<?xml version="1.0"?>
<SESSION>
<CLIENT>$this->username</CLIENT>
<PW>$this->password</PW>
<MSGLST>
EOMSG;

        // Make it one.
		if (!is_array($receivers))
            $receivers = array($receivers);
		
        foreach ($receivers as $number) {
            if (strlen((string)$number) == $this->national_number_lenght) $number = $this->default_country_prefix . $number;
            $msg .= <<<EOMSG
<MSG>
<TEXT>$message</TEXT>
<RCV>$number</RCV>
<SND>$this->smsfrom</SND>
</MSG>
EOMSG;
        }
		
		$msg .="\n</MSGLST>\n</SESSION>";
	
        $mail = (new Email())
            ->subject("SMS")
            ->from($this->mailfrom)
            ->to($this->mailto)
            ->text($msg)
        ;

        $headers = $mail->getHeaders();
        $headers->addTextHeader('Return-Path', $this->mailfrom);
        $headers->addTextHeader('X-Sender', $this->mailfrom);
        // $headers->addTextHeader('Content-Disposition', 'inline');
        $headers->removeAll('Content-Transfer-Encoding');
        $headers->addTextHeader('Content-Transfer-Encoding',  '8bit');
        $this->container->get('mailer')->send($mail);
    }
}
