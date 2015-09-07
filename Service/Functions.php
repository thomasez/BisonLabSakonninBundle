<?php

namespace BisonLab\SakonninBundle\Service;

/**
 * Functions service. This handles the callback and forward functions, both
 * listing and firing.
 */
class Functions
{

    private $container;

    private $forward_functions;
    private $callback_functions;

    public function __construct($container, $Sakonnin_classes = array())
    {
        $this->container         = $container;
        foreach ($Sakonnin_classes as $class) {
            $sakonnin_object = new $class($container, array());

            $this->Sakonnin_classes[] = $sakonnin_object;
            $forward_functions = $sakonnin_object->getForwardFunctions();
            foreach ($forward_functions as $p => $config) {
                if (!isset($config['class']))  $config['class'] = $class;
                $this->forward_functions[$p] = $config;
            }

            $callback_functions = $sakonnin_object->getCallbackFunctions();
            foreach ($callback_functions as $r => $config) {
                if (!isset($config['class']))  $config['class'] = $class;
                $this->callback_functions[$r] = $config;
            }
        }
    }

    public function getForwardsAsChoices() {
        $choices = array();
        foreach ($this->forward_functions as $p => $c) {
            $choices[$p] = $c['description'];
        }
        return $choices;
    }

    public function getCallbacksAsChoices() {
        $choices = array();
        foreach ($this->callback_functions as $p => $c) {
            $choices[$p] = $c['description'];
        }
        return $choices;
    }
    
    private function _getManager()
    {
        if (!$this->entityManager) {
            $this->entityManager 
                = $this->container->get('doctrine')->getManager();
        }
        return $this->entityManager;
    } 
}
