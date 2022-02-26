<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\Message;
use Symfony\Component\Mime\Email;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

/*
 */

trait CommonFunctions
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    protected $container;
    protected $router;
    protected $mailer;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
        $this->mailer = $options['mailer'];
    }

    public function sendMail($message, $mailto, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        /*
         * This is odd, why not just have the receiver address in the message
         * before it's sent to this function?
         * I had my reasons, but the To property works aswell.
         */
        if (empty($mailto)) {
            $mailto = $message->getTo();
        }

        /*
         * Email addresses should be validated before they arrive here.
         * But I am not happy with throwing an exception since most users
         * cannot understand it.
         * Which means I am doing somthign someonme would consider even worse,
         * ignoring it.
         */
        $validator = new EmailValidator();
        if (!$validator->isValid($mailto, class_exists(MessageIDValidation::class) ? new MessageIDValidation() : new RFCValidation())) {
            return false;
        }

        $body = '';
        if (isset($options['provide_link']) && $options['provide_link']) {
            $router = $this->getRouter();
            $url = $router->generate('message_show',
                array('message_id' => $message->getMessageId()), true);
            $body .= "Link to this message: " . $url  . "\n\n";
        }


        $body .= $message->getBody();
        if (!$from = $message->getFrom()) {
            $from = $sm->getEmailFromUser();
            $message->setFrom($from);
            $message->setFromType('EMAIL');
        }

        $message->setToType('EMAIL');
        $mail = (new Email())
            ->subject($message->getSubject())
            ->from($from)
            ->to($mailto)
            ->text($body)
        ;

        /*
         * Let's handle attachments aswell.
         */
        if (isset($options['attach_from_path'])) {
            $content_type = $options['attach_content_type'] ?? null;
            $filename = $options['attach_filename'] ?? basename($options['attach_from_path']);
            $mail->attachFromPath($options['attach_from_path'], $filename, $content_type);
        }
        if (isset($options['attach_content'])) {
            $filename = $options['attach_filename'] ?? "Attachment";
            $mail->attach($options['attach_content'], $filename);
        }

        $this->mailer->send($mail);
        return true;
    }

    public function sendNotification($to, $body, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        // Receiver should/could be userid, username or user object.
        if (!is_object($to)) {
            if (is_numeric($to)) {
                $to = $this->getUserFromUserId($to);
            } else {
                $to = $this->getUserFromUserName($to);
            }
            if (!$to)
                return false;
        }
        $message = new Message();
        $em = $this->getDoctrineManager();

        $message_type = $options['message_type'] ?? "Notification";
        $content_type = $options['content_type'] ?? "text/plain";

        $message->setMessageType(
            $em->getRepository('BisonLabSakonninBundle:MessageType')
                  ->findOneByName($message_type)
        );
        $message->setContentType($content_type);

        $message->setTo($to->getId());
        $message->setToType('INTERNAL');
        $message->setBody($body);

        $from = $this->getLoggedInUser();
        $message->setFrom($from->getId());
        $message->setFromType('INTERNAL');
        // I'll let it contain HTML. This is a security risk if the message
        // contains the wrong HTML or includes something from the outside.
        $message->setContentType('text/html');

        $this->container->get('sakonnin.messages')->postMessage($message);

        return true;
    }

    public function sendSms($message, $receiver, $options = array())
    {
        $sms_handler = $this->container->get('sakonnin.sms_handler');
        if (is_array($receiver))
            $sms_handler->send($message->getBody(), $receiver, $options);
        elseif (is_numeric($receiver))
            $sms_handler->send($message->getBody(), $receiver, $options);
        elseif ($number = $this->extractMobilePhoneNumberFromReceiver($receiver))
            $sms_handler->send($message->getBody(), $number, $options);

        return true;
    }

    // This should be put in a trait later.
    public function extractMobilePhoneNumberFromReceiver($receiver)
    {
        if (is_object($receiver) && method_exists($receiver, "getMobilePhoneNumber"))
            return $receiver->getMobilePhoneNumber();
        if (is_string($receiver) || is_numeric($receiver))
            return $receiver;
        // But what to do now? :=)
        return null;
    }

    // This should be put in a trait later.
    public function extractEmailFromReceiver($receiver)
    {
        $sm = $this->container->get('sakonnin.messages');
        if (is_object($receiver) && method_exists($receiver, "getEmail"))
            return $receiver->getEmail();
        if ($receiver instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return $sm->getEmailFromUser($receiver);
        // In case of userid
        } elseif (preg_match("/\w+@\w+/", $receiver)) {
            // Let's assume this is the email address we're sending to. 
            return $receiver;
        } else {
            return $this->getEmailFromUser($receiver);
        }
        return null;
    }

    public function getRouter()
    {
        if (!$this->router) {
            $this->router = $this->container->get('router');
        }
        return $this->router;
    }
}
