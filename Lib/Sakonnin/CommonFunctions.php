<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\Message;

/*
 */

trait CommonFunctions
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    protected $container;
    protected $router;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function sendMail($message, $receiver, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        // Just a pure user object?
        if ($receiver instanceof \FOS\UserBundle\Model\User) {
            $receiver = $sm->getEmailFromUser($receiver);
        // In case of userid
        } elseif (is_numeric($receiver)) {
            // Gotta find email address user.
            $userManager = $this->container->get('fos_user.user_manager');
            if (!$user = $userManager->findUserBy(array('id'=>$receiver)))
                return false;
            $receiver = $sm->getEmailFromUser($user);
        // In case of not something resembling a mail address, we're guessing
        // username.
        } elseif (!preg_match("/\w+@\w+/", $receiver)) {
            // Gotta find username.
            $userManager = $this->container->get('fos_user.user_manager');
            if (!$user = $userManager->findUserBy(array('username'=>$receiver)))
                return false;
            $receiver = $sm->getEmailFromUser($user);
        }
        // OK, we're hoping we've filtered enough and are stuck with an email
        // address

        $body = '';
        if (isset($options['provide_link'])) {
            $router = $this->getRouter();
            $url = $router->generate('message_show', array('id' => $message->getId()), true);
            $body .= "Link to this message: " . $url  . "\n\n";
        }

        $body .= $message->getBody();
        if (!$from = $message->getFrom()) {
            $from = $sm->getEmailFromUser();
            $message->setFrom($from);
            $message->setFromType('EMAIL');
        }
        $mail = \Swift_Message::newInstance()
        ->setSubject($message->getSubject())
        ->setFrom($from)
        ->setTo($receiver)
        ->setBody($body,
            'text/plain'
        ) ;
        $this->container->get('mailer')->send($mail);
        return true;
    }

    public function sendNotification($to, $body, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        // Receiver should/could be userid, username or user object.
        if (!is_object($to)) {
            if (is_numeric($to)) {
                // Gotta find user.
                $userManager = $this->container->get('fos_user.user_manager');
                if (!$to = $userManager->findUserBy(array('id' => $to)))
                    return false;
            } else {
                $userManager = $this->container->get('fos_user.user_manager');
                if (!$to = $userManager->findUserBy(array('username' => $to)))
                    return false;
            }
        }
        $message = new Message();
        $em = $this->getDoctrineManager();

        $message_type = $options['message_type'] ?: "Notification";
        $content_type = $options['content_type'] ?: "text/plain";

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
        if ($number = $this->extractMobilePhoneNumberFromReceiver($receiver))
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
        if (is_object($receiver) && method_exists($receiver, "getEmail"))
            return $receiver->getEmail();
        if (is_string($receiver))
            return $receiver;
        // But what to do now? :=)
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
