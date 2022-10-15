<?php

namespace BisonLab\SakonninBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;

class Builder
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function messageMenu(FactoryInterface $factory, array $options): ItemInterface
    {
        if (isset($options['menu'])) {
            $menu = $options['menu'];
        } else {
            $menu = $factory->createItem('root');
        }

        // Gotta create routes to the message boxes.
        $read_new_route = $this->router->generate('message_unread', array('access' => 'ajax'));
        $read_new_click = 'openSakonninMessageLogBox("' . $read_new_route . '")';
        $message_log_route = $this->router->generate('message', array('access' => 'ajax'));
        $message_log_click = 'openSakonninMessageLogBox("' . $message_log_route . '")';

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
