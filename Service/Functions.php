<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * Functions service. This handles the callback and forward functions, both
 * listing and firing.
 */
class Functions
{
    private $locator;
    private $parameterBag;

    private $forward_functions;
    private $callback_functions;

    public function __construct(ServiceLocator $locator, ParameterBagInterface $parameterBag)
    {
        $this->locator = $locator;
        $this->parameterBag = $parameterBag;

        foreach ($this->locator->getProvidedServices() as $sclass) {
            $sf = $this->locator->get($sclass);

            $forward_functions = $sf->getForwardFunctions();
            foreach ($forward_functions as $p => $config) {
                $config['class'] = $sclass;
                $this->forward_functions[$p] = $config;
            }

            $callback_functions = $sf->getCallbackFunctions();
            foreach ($callback_functions as $r => $config) {
                $config['class'] = $sclass;
                $this->callback_functions[$r] = $config;
            }
        }
    }

    public function getForwardsAsChoices()
    {
        $choices = array();
        foreach ($this->forward_functions as $p => $c) {
            // $choices[$p] = $c['description'];
            $choices[$c['description']] = $p;
        }
        return $choices;
    }

    public function getCallbacksAsChoices()
    {
        $choices = array();
        foreach ($this->callback_functions as $p => $c) {
            $choices[$c['description']] = $p;
        }
        return $choices;
    }
    
    /*
     * The issue not really decided yet is "When to fire forward functions and
     * when to fire callbacks?". One thing we do know, is that a new message
     * is forwarded and a reply triggers a callback.  
     * But what about a reply on a reply?
     */
    public function dispatchMessageFunctions(Message $message, $options)
    {
        $messagetype = $message->getMessageType();
        $function = null;
        $attributes = null;
        $config = null;
        if ($message->getInReplyTo()) {
            $function = $messagetype->getCallbackFunction();
            if (!$function || !isset($this->callback_functions[$function])) {
                // Nutti'n to do. (Should I throw exception if they tried to
                // call a non-existant function? It's probably good to do,
                // security wise. But what to do with the info?
                return true;
            }
            $config = $this->callback_functions[$function];
            $attributes = $messagetype->getCallbackAttributes();
        } else {
            $function = $messagetype->getForwardFunction();
            if (!$function || !isset($this->forward_functions[$function])) {
                // Nutti'n to do. (Should I throw exception if they tried to
                // call a non-existant function? It's probably good to do,
                // security wise. But what to do with the info?
                return true;
            }
            $config = $this->forward_functions[$function];
            $attributes = $messagetype->getForwardAttributes();
        }

        $sfunc = $this->locator->get($config['class']);
        // I prefer wrong and working.
        $sfunc->setSakonninMessages($options['sakonninMessages']);
        $sfunc->setSmsHandler($options['smsHandler'] ?? null);

        // Add more if you need to.
        $options['user']       = $user;
        $options['message']    = $message;
        $options['attributes'] = $attributes;
        $options['function']   = $function;
        $options['config']     = $config;
        return $sfunc->execute($options);
    }

    /* 
     * The dispatch functions regarding files.
     *
     * Not in use yet. (Had a plan about thumbnailing, but decided to create
     * those on demand (but cache).
     * 
     */
    public function dispatchFileFunctions(SakonninFile $sfile)
    {
        return true;
    }
}
