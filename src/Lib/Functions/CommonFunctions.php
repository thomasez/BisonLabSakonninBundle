<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Entity\MessageContext;
use Symfony\Component\Mime\Email;

/*
 * Used services:
 * 
 * Symfony\Component\Mailer\MailerInterface $this->mailer;
 * Symfony\Component\Routing\RouterInterface $this->router;
 *
 * Cicular typehinting is not really working.
 * Which is why it's setters further down.
 * BisonLab\SakonninBundle\Service\Messages as SakonninMessages $this->sakonninMessages;
 * BisonLab\SakonninBundle\Service\SmsHandler $this->smsHandler
 */

trait CommonFunctions
{
    protected $sakonninMessages;

    public function setSakonninMessages($sakonninMessages)
    {
        $this->sakonninMessages = $sakonninMessages;
    } 

    public function getCallbackFunctions()
    {
        return $this->callback_functions;
    } 

    public function getForwardFunctions()
    {
        return $this->forward_functions;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function sendMail(Message $message, $mailto, $options = array())
    {
        /*
         * This is odd, why not just have the receiver address in the message
         * before it's sent to this function?
         * I had my reasons, but the To property works aswell.
         */
        if (empty($mailto)) {
            $mailto = $message->getTo();
        }

        $body = '';
        if (isset($options['provide_link']) && $options['provide_link']) {
            $url = $this->router->generate('message_show',
                array('message_id' => $message->getMessageId()), true);
            $body .= "Link to this message: " . $url  . "\n\n";
        }

        $body .= $message->getBody();
        if (!$from = $message->getFrom()) {
            $from = $this->sakonninMessages->getEmailFromUser();
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

    public function sendNotification($to, $body, $options = array()): ?Message
    {
        // Receiver should/could be userid, username or user object.
        if (!is_object($to)) {
            if (is_numeric($to)) {
                $to = $this->sakonninMessages->getUserFromUserId($to);
            } else {
                $to = $this->sakonninMessages->getUserFromUserName($to);
            }
            if (!$to)
                return null;
        }
        $message = new Message();

        $message_type = $options['message_type'] ?? "Notification";

        $message->setMessageType($this->sakonninMessages
            ->getMessageType($message_type));
        // I'll let it contain HTML. This is a security risk if the message
        // contains the wrong HTML or includes something from the outside.
        $message->setContentType('text/html');

        $message->setTo($to);
        $message->setToType('INTERNAL');
        $message->setBody($body);

        $from = $this->sakonninMessages->getLoggedInUser();
        $message->setFrom($from);
        $message->setFromType('INTERNAL');

        if ($context_data = $options['context'] ?? null) {
            $message_context = new MessageContext($context_data);
            $message->addContext($message_context);
        }

        $this->sakonninMessages->postMessage($message);

        return $message;
    }

    public function sendSms($message, $receiver, $options = array())
    {
        // Argh!
        $this->smsHandler->setSakonninMessages($this->sakonninMessages);

        if (is_array($receiver))
            $this->smsHandler->send($message->getBody(), $receiver, $options);
        elseif (is_numeric($receiver))
            $this->smsHandler->send($message->getBody(), $receiver, $options);
        elseif ($number = $this->extractMobilePhoneNumberFromReceiver($receiver))
            $this->smsHandler->send($message->getBody(), $number, $options);

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

    public function extractEmailFromReceiver($receiver)
    {
        if (is_object($receiver) && method_exists($receiver, "getEmail"))
            return $receiver->getEmail();
        if ($receiver instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return $this->sakonninMesages->getEmailFromUser($receiver);
        // In case of userid
        } elseif (preg_match("/\w+@\w+/", $receiver)) {
            // Let's assume this is the email address we're sending to. 
            return $receiver;
        } else {
            return $this->sakonninMessages->getEmailFromUser($receiver);
        }
        return null;
    }
}
