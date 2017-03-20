<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

trait CommonFunctions
{
    protected $container;
    protected $router;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function sendMail($message, $receiver, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        // In case of userid
        if (is_numeric($receiver)) {
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

    public function sendPm($message, $receiver, $options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        // Receiver should/could be userid, username or user object.
        if (!is_numeric($receiver)) {
            // Gotta find user.
            $userManager = $this->container->get('fos_user.user_manager');
            if (!$user = $userManager->findUserBy(array('username' => $receiver)))
                return false;
            $receiver = $user->getId();
        }
        $message->setTo($receiver);
        $message->setToType('INTERNAL');
        $message->setBody($body);

        if (!$from = $message->getFrom()) {
            $user = $sm->getLoggeInUser();
            $message->setFrom($user->getId());
            $message->setFromType('INTERNAL');
        }

        $this->container->get('sakonnin')->postMessage($message);

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
