<?php

namespace BisonLab\SakonninBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function messageMenu(FactoryInterface $factory, array $options)
    {
        $menu = $container = null;
        if (isset($options['menu'])) {
            $menu = $options['menu'];
        } else {
            $menu = $factory->createItem('root');
        }
        if (isset($options['container'])) {
            $container = $options['container'];
        } else {
            $container = $this->container;
        }

        // Gotta create routes to the message boxes.
        $router = $container->get('router');
        $read_new_route = $router->generate('message_unread', array('access' => 'ajax'));
        $read_new_click = 'openMessageLogBox("' . $read_new_route . '")';
        $message_log_route = $router->generate('message', array('access' => 'ajax'));
        $message_log_click = 'openMessageLogBox("' . $message_log_route . '")';

        $menu->addChild('Messages');
        $menu['Messages']->addChild('Unread Messages', array('uri' => '#'));
        $menu['Messages']['Unread Messages']->setAttribute('id', 'menu_unread');
        $menu['Messages']['Unread Messages']->setLinkAttribute('onclick', $read_new_click);
        $menu['Messages']->setAttribute('id', 'message_menu');
        $menu['Messages']->addChild('Message History', array( 'route' => 'message'));
        $menu['Messages']->addChild('Send Personal Message', array('uri' => '#'));
        $menu['Messages']['Send Personal Message']->setLinkAttribute('onclick', 'createPmMessage()');
        $menu['Messages']['Send Personal Message']->setLinkAttribute('id', 'createPmMenu');
        return $menu;
    }
}
