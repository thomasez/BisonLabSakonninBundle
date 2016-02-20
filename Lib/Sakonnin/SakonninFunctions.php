<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

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
            'description' => "Forward to mail adress(es)",
            'needs_attributes' => true,
        ),
        'ForwardOnErrorSubject' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\ForwardOnErrorSubject',
            'description' => "Forward to user when the subject has the word error in it.",
            'needs_attributes' => false,
        )
    );

    public $forward_functions = array(
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es)",
            'needs_attributes' => true,
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
