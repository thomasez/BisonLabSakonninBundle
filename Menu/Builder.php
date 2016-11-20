<?php

namespace BisonLab\SakonninBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/* 
 * Hopefully not required.
 * (A script in scripts.html.twig does check for unread and sets the color 
 * of "Messages" here to red.)
 */

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function messageMenu(FactoryInterface $factory, array $options, $menu = null)
    {
        $menu->addChild('Messages');
        $menu['Messages']->setAttribute('id', 'message_menu');
        $menu['Messages']->addChild('Read new messages', 
                array('route' => 'message_unread'));
        $menu['Messages']['Read new messages']->setAttribute('id', 'menu_unread');
        $menu['Messages']->addChild('My message log', array( 'route' => 'message'));
        $menu['Messages']->addChild('Write PM', array('uri' => '#'));
        $menu['Messages']['Write PM']->setLinkAttribute('onclick', 'createPmMessage()');
        return $menu;
    }
}
