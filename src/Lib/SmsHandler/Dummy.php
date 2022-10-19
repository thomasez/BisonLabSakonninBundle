<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

/*
 * The by far simplest way..
 * to do nothing at all.
 */

class Dummy
{
    use \BisonLab\SakonninBundle\Lib\Functions\CommonFunctions;

    public $config = [
        'name' => 'dummy',
        'description' => "SMSes goes nowhere",
        'sends' => true,
        'receives' => false,
    ];

    public function __construct($options = array())
    {
    }

    public function send($message, $receivers, $options = array())
    {
        return true;
    }
}
