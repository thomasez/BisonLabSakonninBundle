<?php

// I'm just not bothered putting this in it's own directory. Service it is' not
// Security.
namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Entity\MessageType;

/*
 * Blatantly cooked from the docs.. 
 * http://symfony.com/doc/current/security/voters.html
 */
class SecurityModelVoter extends Voter
{
    private $external_retriever;

    public function __construct($external_retriever)
    {
        $this->external_retriever         = $external_retriever;
    }

    protected function supports($attribute, $subject)
    {
        // Gotta handle all operations.
        if ($subject instanceof Message 
            || $subject instanceof MessageType
            || $subject instanceof MessageContext)
            return true;
        else
            return false;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // If it does not have any security model set, don't bother.
        if (!$security_model = $subject->getSecurityModel()) {
            return true;
        }

        // This can be both a Message and a Message Type.
        switch ($security_model) {
            case 'ALL_READ':
                if (in_array($attribute, array('show', 'index')))
                    return true;
                elseif (in_array($attribute, array('delete', 'edit', 'create')))
                    return $this->_isAdmin($token);
                break;
            case 'ALL_READWRITE':
                return true;
                break;
            case 'ADMIN_ONLY':
                return $this->_isAdmin($token);
                break;
            case 'PRIVATE':
                return $this->_checkPrivate($attribute, $subject, $token);
                break;
            default:
                return false;
        }
        return false;
    }

    /* 
     * This is quite against the concept. I think.
     * But I have to use the role system here since I have no clue what the
     * bundle users use.
     */
    private function _isAdmin($token)
    {
        foreach ($token->getRoles() as $role) {
            if (in_array($role->getRole(), ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
                return true;
            }
        }
        return false;
    }

    /*
     * If User is ether sender, receiver og creator, OK.
     * There is one issue here.
     * I dunno how to check/vote when we vote on a message type.
     * The type itself ain't an issue, but the messages connected to it is.
     * We have to handle them somewhere else, somehow.
     *
     * How we handle this is also dependant on the message type, since
     * basically everything else than INTERNAL is just loosely connected
     * to a user. Like username (which can change)
     *
     * But it doesen't really matter, that would be a problem anyway.
     *
     * Another check is against the contexts. If they point at the same object,
     * it's the user.
     */
    private function _checkPrivate($atttribute, $subject, $token)
    {
        if ($subject instanceof MessageContext)
            $subject = $subject->getMessage();

        $user = $token->getUser();

        if ($subject instanceof Message) {
            if (('INTERNAL' == $subject->getFromType())
                && $subject->getFrom() == $user->getId())
                    return true;
            if (('INTERNAL' == $subject->getToType())
                && $subject->getTo() == $user->getId())
                    return true;
            // Then, how do I get the object the context is pointing at?
            // Answer: "The ExternalRetriever" in my CommonBundle.
            foreach ($subject->getContexts() as $context) {
                if ($object = $this->external_retriever->getExternalDataFromContext($context)) {
                    // The question now is. How do I know that the object is
                    // what I am looking for?
                    // For now, I presume it's within the same application and
                    // do a pure match.
                    if ($object === $user)
                        return true;
                }
            }
        } else {
            return true;
        }
        return false;
    }
}
