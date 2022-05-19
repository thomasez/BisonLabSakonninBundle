<?php

namespace BisonLab\SakonninBundle\Lib;

use BisonLab\SakonninBundle\Entity\MessageType;

/*
 */

trait CommonStuff 
{
    private $entityManager;
    private $sakonmin_messages;
    private $sakonmin_functions;

    public function setSakonninMessages($sm)
    {
        $this->sakonmin_messages = $sm;
    }

    public function setSakonninFunctions($sf)
    {
        $this->sakonmin_functions = $sf;
    }

    public function getLoggedInUser()
    {
        // Note to whoever: Controllers have "$this->getUser()", but this is
        // not one.
        if (!$this->container) return null;
        if (!$this->container->get('security.token_storage')) return null;
        if (!$this->container->get('security.token_storage')->getToken()) return null;
        return $this->container->get('security.token_storage')->getToken()->getUser();
    }

    public function getUserFromUserId($userid)
    {
        $user_repo = $this->getUserRepository();
        return $user_repo->find($userid);
    }

    public function getUserFromUserName($username)
    {
        $user_repo = $this->getUserRepository();
        return $user_repo->findOneBy(array('username' => $username));
    }

    public function getUserNameFromUserId($userid)
    {
        // It may just not be an ID.
        if (!is_numeric($userid)) return $userid;
        $user = $this->getUserFromUserId($userid);
        if (!$user) return $userid;
        return $user->getUserName();;
    }

    public function getEmailFromUser($user = null)
    {
        if (!$user)
            $user = $this->getLoggedInUser();
        // It may just be an ID.
        if (is_numeric($user)) {
            $user = $this->getUserFromUserId($user);
        }
        // Or string?
        if (is_string($user)) {
            $user = $this->getUserFromUserName($user);
        }

        if (is_object($user) && method_exists($user, 'getEmail'))
            return $user->getEmail();
        return null;
    }

    /* For finding a number to send SMSes to. It's still mobiles. */
    public function getMobilePhoneNumberFromUser($user = null)
    {
        if (!$user)
            $user = $this->getLoggedInUser();
        // It may just be an ID.
        if (is_numeric($user)) {
            $user = $this->getUserFromUserId($user);
        }
        // Or string?
        if (is_string($user)) {
            $user = $this->getUserFromUserName($user);
        }

        if (is_object($user) && method_exists($user, 'getMobilePhoneNumber'))
            return $user->getMobilePhoneNumber();
        if (is_object($user) && method_exists($user, 'getPhoneNumber'))
            return $user->getPhoneNumber();
        return null;
    }

    public function getMessageType($name)
    {
        if ($name instanceof MessageType)
            return $name;
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository(MessageType::class);
        return $repo->findOneByName($name);
    }

    public function getMessageTypes($criterias)
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository(MessageType::class);
        if (isset($criterias['base_type'])) {
            return $repo->findBy(['base_type' => $criterias['base_type']]);
        }
        return [];
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

    public function getUserEntityManager()
    {
        if (!isset($this->container))
            $this->container = $this->getContainer();
        $user_class = $this->container->getParameter('sakonnin.user')['entity'];
        return $this->container->get('doctrine')->getManagerForClass($user_class);
    }

    public function getUserRepository()
    {
        if (!isset($this->container))
            $this->container = $this->getContainer();
        $user_class = $this->container->getParameter('sakonnin.user')['entity'];
        return $this->getUserEntityManager()->getRepository($user_class);
    }
}
