<?php

namespace BisonLab\SakonninBundle\Lib;

/*
 */

trait CommonStuff 
{
    private $entityManager;

    public function getDoctrineManager()
    {
        // This is a fallback. It may even handle the cases it's needed.
        // (Mainly Commands)
        if (!isset($this->container))
            $this->container = $this->getContainer();
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
