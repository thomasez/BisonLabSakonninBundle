<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/*
 * The simplest way to send an SMS via pswin.com. Hopefully not lasting for
 * long. It's nicked from the old CrewCall and does not look good at all.
 */
class PsWinComMail
{
    use \BisonLab\SakonninBundle\Lib\Functions\CommonFunctions;

    protected $options;

    public $config = [
        'name' => 'pswincom_mail',
        'description' => "SMS via pswin.com old mailinterface",
        'sends' => true,
        'receives' => false,
    ];

    public function __construct(
            ParameterBagInterface $parameterBag,
            MailerInterface $mailer
    ) {
        $this->options = $parameterBag->get('sakonnin.sms');
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
	
        $mail = (new Email())
            ->subject("SMS")
            ->from($this->options['mailfrom'])
            ->to($this->options['mailto'])
            ->text($msg)
        ;

        $headers = $mail->getHeaders();
        $headers->addTextHeader('Return-Path', $this->options['mailfrom']);
        $headers->addTextHeader('X-Sender', $this->options['mailfrom']);
        // $headers->addTextHeader('Content-Disposition', 'inline');
        $headers->removeAll('Content-Transfer-Encoding');
        $headers->addTextHeader('Content-Transfer-Encoding',  '8bit');
        $this->mailer->send($mail);
    }
}
