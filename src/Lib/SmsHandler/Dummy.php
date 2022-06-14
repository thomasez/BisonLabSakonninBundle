<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

/*
 * The by far simplest way..
 * to do nothing at all.
 */

class Dummy
{
//    use \BisonLab\SakonninBundle\Lib\Functions\CommonFunctions;

    public function __construct()
    {
    }

    public function send($message, $receivers, $options = array())
    {
        return true;
    }
}
