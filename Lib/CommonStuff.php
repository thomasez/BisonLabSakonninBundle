<?php

namespace BisonLab\SakonninBundle\Lib;

/*
 */

trait CommonStuff 
{
    private $entityManager;

    public function getLoggedInUser()
    {
        // Note to whoever: Controllers have "$this->getUser()", but this is
        // not one.
        if (!$this->container) return null;
        if (!$this->container->get('security.token_storage')) return null;
        if (!$this->container->get('security.token_storage')->getToken()) return null;
        return $this->container->get('security.token_storage')->getToken()->getUser();
    }

    public function getUserNameFromUserId($userid)
    {
        // It may just not be an ID.
        if (!is_numeric($userid)) return $userid;
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $userid));
        if (!$user) return $userid;
        return $user->getUserName();;
    }
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
