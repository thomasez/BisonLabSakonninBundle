<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Lib\Sakonnin\SakonninFunctionsInterfac;

/*
 */

class SakonninFunctions implements SakonninFunctionsInterface
{

    protected $container;

    public $callback_functions = array(
        // Gawd I'm annoyed by this, MailForward or ForwardMail? What's the
        // most correct?
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es)"
        )
    );

    public $forward_functions = array(
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es)"
        )
    );

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function getCallbackFunctions()
    {
        return $this->callback_functions;
    }

    public function getForwardFunctions()
    {
        return $this->forward_functions;
    }

}
