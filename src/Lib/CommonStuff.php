<?php

namespace BisonLab\SakonninBundle\Lib;

use BisonLab\SakonninBundle\Entity\MessageType;

/*
 * As the name says. Functions used everywhere.
 *
 * This needs these services:
 *  * Doctrine\Persistence\ManagerRegistry $managerRegistry
 *  * Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage 
 */

trait CommonStuff 
{
    private $entityManager;

    public function getLoggedInUser()
    {
        // Note to whoever: Controllers have "$this->getUser()", but this is
        // not one.
        if (!$token = $this->tokenStorage->getToken()) return null;
        return $token->getUser();
    }

    public function getUserFromUserId($userid)
    {
        $user_repo = $this->getUserRepository();
        return $user_repo->find($userid);
    }

    public function getUserFromUserIdentifier($identifier)
    {
        $user_repo = $this->getUserRepository();
        return $user_repo->findOneBy(array('identifier' => $identifier));
    }

    public function getUserFromUserName($username)
    {
        $user_repo = $this->getUserRepository();
        $c = $user_repo->getClassName();
        if (property_exists($c, 'username'))
            return $user_repo->findOneBy(array('username' => $username));
        else
            return $this->getUserFromUserIdentifier($username);
    }

    public function getUserFromEmail($email)
    {
        $user_repo = $this->getUserRepository();
        $c = $user_repo->getClassName();
        if (property_exists($c, 'email'))
            return $user_repo->findOneBy(array('email' => $email));
        else
            return $this->getUserFromUserIdentifier($email);
    }

    public function getUsernameFromUserId($userid)
    {
        // It may just not be an ID.
        if (!is_numeric($userid)) return $userid;
        $user = $this->getUserFromUserId($userid);
        if (!$user) return $userid;
        if (method_exists($user, 'getUsername'))
            return $user->getUsername();
        else
            return $user->getUserIdentifier();
    }

    public function getUserIdentifierFromUserId($userid)
    {
        // It may just not be an ID.
        if (!is_numeric($userid)) return $userid;
        $user = $this->getUserFromUserId($userid);
        return $user->getUserIdentifier();
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
        if (!$this->entityManager) {
            // Check if the manager exists.
            if (in_array('sakonnin', 
                    array_keys($this->managerRegistry
                    ->getManagerNames()))) {
                $this->entityManager
                  = $this->managerRegistry->getManager('sakonnin');
            } else {
                // Well, use the default then.
                $this->entityManager
                    = $this->managerRegistry->getManager();
            }
        }
        return $this->entityManager;
    }

    public function getUserEntityManager()
    {
        $user_class = $this->parameterBag->get('sakonnin.user')['entity'];
        return $this->managerRegistry->getManagerForClass($user_class);
    }

    public function getUserRepository()
    {
        $user_class = $this->parameterBag->get('sakonnin.user')['entity'];
        return $this->getUserEntityManager()->getRepository($user_class);
    }
}
