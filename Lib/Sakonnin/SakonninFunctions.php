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
        'sendpmtouserlist' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\SendPmToUserList',
            'description' => "Send PM",
            'attribute_spec' => "Username",
            'needs_attributes' => true,
        ),
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es)",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
        'ForwardOnErrorSubject' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\ForwardOnErrorSubject',
            'description' => "Forward to user when the subject has the word error in it.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'MailAndPmOnErrorSubject' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailAndPmOnErrorSubject',
            'description' => "Send mail and PM to user when the subject has the word error in it.",
            'attribute_spec' => "Username",
            'needs_attributes' => true,
        ),
    );

    public $forward_functions = array(
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es)",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
        'Broadcast' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\Broadcast',
            'description' => "Send PM to all enabled users",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'sendpmtouserlist' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\WritePm',
            'description' => "Send PM",
            'attribute_spec' => "Username",
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
