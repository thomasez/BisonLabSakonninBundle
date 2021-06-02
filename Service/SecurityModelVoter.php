<?php

// I'm just not bothered putting this in it's own directory. Service it is' not
// Security.
namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
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
    private $sakonnin_messages;

    public function __construct($external_retriever, $sakonnin_messages)
    {
        $this->external_retriever = $external_retriever;
        $this->sakonnin_messages  = $sakonnin_messages;
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
            return false;
        }

        // If the subject itself decides it's not editable, return false
        if (method_exists($subject, 'isEditable')
            && $attribute == "edit"
            && !$subject->isEditable()) {
                return false;
            }

        // If the subject itself decides it's not deleteable, return false
        if (method_exists($subject, 'isDeleteable')
            && $attribute == "delete"
            && !$subject->isDeleteable())
                return false;

        // This can be both a Message and a Message Type.
        switch ($security_model) {
            case 'ALL_READ':
                if (in_array($attribute, array('show', 'index')))
                    return true;
                elseif (in_array($attribute, array('delete', 'edit', 'create')))
                    return $this->_isAdmin($user, $token);
                break;
            case 'ALL_READWRITE':
                return true;
                break;
            case 'ADMIN_ONLY':
                return $this->_isAdmin($user, $token);
                break;
            case 'ADMIN_RW_USER_R':
                if (in_array($attribute, array('show', 'index')))
                    return $this->_isAdmin($user, $token) || $this->_checkPrivate($attribute, $subject, $token);
                elseif (in_array($attribute, array('delete', 'edit', 'create')))
                    return $this->_isAdmin($user, $token);
                break;
            case 'ADMIN_RW_USER_RW':
                return $this->_isAdmin($user, $token) || $this->_checkPrivate($attribute, $subject, $token);
                break;
            case 'GROUP_RW':
                return $this->_hasGroup($attribute, $subject, $token);
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
    private function _isAdmin($user, $token)
    {
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }
        if (method_exists($token, 'getRoleNames')) {
            foreach ($token->getRoleNames() as $role) {
                if (in_array($role, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
                    return true;
                }
            }
        } else {
            foreach ($token->getRoles() as $role) {
                if (in_array($role->getRole(), ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
                    return true;
                }
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

        if (!$subject instanceof Message) {
            return false;
        }

        if (('INTERNAL' == $subject->getFromType()) && 
            ($subject->getFrom() == $user->getId() || $subject->getFrom() == $user->getUsername()))
                return true;
        if (('INTERNAL' == $subject->getToType()) &&
            ($subject->getTo() == $user->getId() || $subject->getTo() == $user->getUsername()))
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
        return false;
    }

    /*
     * If User is ether sender, receiver og creator, check the group(s) it
     * belongs to.
     * This model does require that you actually use groups..=)
     * Same issues as with _checkPrivate.
     */
    private function _hasGroup($atttribute, $subject, $token)
    {
        if ($subject instanceof MessageContext)
            $subject = $subject->getMessage();

        // Is this correct?
        if (!$subject instanceof Message) {
            return false;
        }

        $user = $token->getUser();
        if (!method_exists($user, 'getGroupNames'))
            return false;

        if ('INTERNAL' == $subject->getFromType() && $from = $subject->getFrom()) {
            if ($from_user = $this->sakonnin_messages->getUserFromUserName($from)) {
                foreach($from_user->getGroupNames() as $gn) {
                    if (in_array($gn, $user->getGroupNames()))
                        return true;
                }
                
                if (in_array($from_user->getGroupNames(), $user->getGroupNames()))
                    return true;
            }
        }
        foreach ($subject->getContexts() as $context) {
            if ($object = $this->external_retriever->getExternalDataFromContext($context)) {
                // The question now is. How do I know that the object is
                // what I am looking for?
                // For now, I presume it's within the same application and
                // do a pure match.
                if (!method_exists($object, 'getGroupNames'))
                    continue;
                
                if (in_array($object->getGroupNames(), $user->getGroupNames()))
                    return true;
            }
        }
        return false;
    }
}
