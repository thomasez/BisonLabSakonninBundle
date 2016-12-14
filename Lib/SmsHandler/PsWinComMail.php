<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

/*
 * The simplest way to send an SMS via pswin.com. Hopefully not lasting for
 * long. It's nicked from the old CrewCall and does not look good at all.
 */

class PsWinComMail
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
            $msg .= <<<EOMSG
<MSG>
<TEXT>$message</TEXT>
<RCV>$number</RCV>
<SND>$this->smsfrom</SND>
</MSG>
EOMSG;
        }
		
		$msg .="\n</MSGLST>\n</SESSION>";
	
error_log($msg);
        $mail = \Swift_Message::newInstance()
        ->setSubject("SMS")
        ->setFrom($this->mailfrom)
        ->setTo($this->mailto)
        ->setBody($msg,
            'text/plain'
        ) ;
        $this->container->get('mailer')->send($mail);
    }
}
