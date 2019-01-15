<?php

namespace BisonLab\SakonninBundle\Lib\SmsHandler;

/*
 * The by far simplest way..
 * to do nothing at all.
 */

class Dummy
{
    use \BisonLab\SakonninBundle\Lib\Sakonnin\CommonFunctions;

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function send($message, $receivers, $options = array())
    {
        return true;
    }
}
