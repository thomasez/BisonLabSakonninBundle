<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

/*
 */

class SakonninFunctions implements SakonninFunctionsInterface
{

    protected $container;

    public $callback_functions = array(
        'sendpmtouserlist' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\SendPmToUserList',
            'description' => "Send PM to everyone in the attributes list.",
            'attribute_spec' => "Username",
            'needs_attributes' => true,
        ),
        // Gawd I'm annoyed by this, MailForward or ForwardMail? What's the
        // most correct?
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Forward to mail adress(es) in attributes list.",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
        'ForwardOnErrorSubject' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\ForwardOnErrorSubject',
            'description' => "Forward to user and addresses in attribute list when the subject has the word error in it.",
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
        'mailcopy' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailCompy',
            'description' => "Send copy of message as mail.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'pmcopy' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\PmCopy',
            'description' => "Send copy of message as PM.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'smscopy' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\SmsCopy',
            'description' => "Send copy of message as SMS.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'mailforward' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\MailForward',
            'description' => "Send copy to mail adress(es) in attributes list",
            'attribute_spec' => "Mail address",
            'needs_attributes' => true,
        ),
        'broadcast' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\Broadcast',
            'description' => "Send PM to all enabled users",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'sendpmtouserlist' => array(
            'class' => 'BisonLab\SakonninBundle\Lib\Sakonnin\WritePm',
            'description' => "Send copy as PM to users in attributes list",
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
