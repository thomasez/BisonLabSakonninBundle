<?php

namespace BisonLab\SakonninBundle\Service;
use BisonLab\SakonninBundle\Entity\Message;

/**
 * Functions service. This handles the callback and forward functions, both
 * listing and firing.
 */
class Functions
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;

    private $forward_functions;
    private $callback_functions;

    public function __construct($container, $Sakonnin_classes = array())
    {
        $this->container         = $container;
        foreach ($Sakonnin_classes as $class) {
            $sakonnin_object = new $class($container, array());

            $forward_functions = $sakonnin_object->getForwardFunctions();
            foreach ($forward_functions as $p => $config) {
                if (!isset($config['class']))  $config['class'] = $class;
                $this->forward_functions[$p] = $config;
            }

            $callback_functions = $sakonnin_object->getCallbackFunctions();
            foreach ($callback_functions as $r => $config) {
                if (!isset($config['class']))  $config['class'] = $class;
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
    
    /* The issue not really decided yet is "When to fire forward functions and
     * when to fire callbacks?". One thing we do know, is that a new message
     * is forwarded and a reply triggers a callback.  
     * But what about a reply on a reply?
     */
    public function dispatchMessageFunctions(Message $message)
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

        $sm = $this->container->get('sakonnin.messages');
        $user = $sm->getLoggedInUser();

        $class = new $config['class']($this->container);
        // Add more if you need to.
        $options = array(
            'user'       => $user,
            'message'    => $message,
            'attributes' => $attributes,
            'function'   => $function,
            'config'     => $config,
        );
        return $class->execute($options);
    }
}
