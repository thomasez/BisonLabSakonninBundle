<?php

namespace BisonLab\SakonninBundle\Lib;

/*
 */

trait CommonStuff 
{
    private $entityManager;

    public function getDoctrineManager()
    {
        if (!$this->entityManager) {
            // Check if the manager exists.
            if (in_array('sakonnin', 
                    array_keys($this->container->get('doctrine')
                    ->getManagerNames()))) {

                $this->entityManager
                    = $this->container->get('doctrine')->getManager('sakonnin');

            } else {

                // Well, use the default then.
                $this->entityManager
                    = $this->container->get('doctrine')->getManager();

            }
        }
        return $this->entityManager;
    }
}
