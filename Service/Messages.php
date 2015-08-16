<?php

namespace BisonLab\SakonninBundle\Service;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BisonLab\SakonninBundle\Entity\Message;

/**
 * Messages service.
 */
class Messages
{

    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

}
