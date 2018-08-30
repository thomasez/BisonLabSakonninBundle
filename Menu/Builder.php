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

        $messagesmenu = $menu->addChild('Messages');
        $messagesmenu->setAttribute('id', 'message_menu');

        $unread = $messagesmenu->addChild('Unread Messages', array('uri' => '#'));
        $unread->setAttribute('id', 'menu_unread');
        $unread->setLinkAttribute('onclick', $read_new_click);

        $messagesmenu->addChild('Message History', array( 'route' => 'message'));
        $pmm = $messagesmenu->addChild('Send Personal Message', array('uri' => '#'));
        $pmm->setLinkAttribute('onclick', 'createPmMessage()');
        $pmm->setLinkAttribute('id', 'createPmMenu');
        return $menu;
    }
}
