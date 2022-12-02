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
use BisonLab\SakonninBundle\Entity\SakonninFile;
use BisonLab\SakonninBundle\Entity\SakonninFileContext;
use BisonLab\SakonninBundle\Entity\SakonninFileType;
use BisonLab\SakonninBundle\Service\Messages as SakonninMessages;
use BisonLab\ContextBundle\Service\ExternalRetriever;

/*
 * Blatantly cooked from the docs.. 
 * http://symfony.com/doc/current/security/voters.html
 */
class SecurityModelVoter extends Voter
{
    public function __construct(
        private ExternalRetriever $externalRetriever,
        private SakonninMessages $sakonninMessages
    ) {
    }

    protected function supports($attribute, $subject): bool
    {
        // Gotta handle all operations.
        if ($subject instanceof Message
            || $subject instanceof MessageType
            || $subject instanceof MessageContext
            || $subject instanceof SakonninFile
            || $subject instanceof SakonninFileType
            || $subject instanceof SakonninFileContext)
            return true;
        else
            return false;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // darn method_exists changed. to something more corrrect tho.
        if (!$subject)
            return false;

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($attribute == "index")
            return true;

        // If the subject itself decides it's not editable, return false
        if (method_exists($subject, 'isEditable')
                && $attribute == "edit"
                && !$subject->isEditable()) {
            return false;
        }

        // If the subject itself decides it's not deleteable, return false
        if (method_exists($subject, 'isDeleteable')
                && $attribute == "delete"
                && !$subject->isDeleteable()) {
            return false;
        }

        // It hurts, but how can I do without?
        if (!method_exists($subject, 'getSecurityModel'))
            return true;

        // If it does not have any security model set, just say no.
        if (!$security_model = $subject->getSecurityModel()) {
            return false;
        }

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
            ($subject->getFrom() == $user->getId() || $subject->getFrom() == $user->getUserIdentifier()))
                return true;
        if (('INTERNAL' == $subject->getToType()) &&
            ($subject->getTo() == $user->getId() || $subject->getTo() == $user->getUserIdentifier()))
                return true;
        // Then, how do I get the object the context is pointing at?
        // Answer: "The ExternalRetriever" in my ContextBundle.
        foreach ($subject->getContexts() as $context) {
            if ($object = $this->externalRetriever->getExternalDataFromContext($context)) {
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
     * If User is ether sender, receiver or creator, check the group(s) it
     * belongs to.
     * This model does require that you actually use groups..=)
     * Same issues as with _checkPrivate.
     */
    private function _hasGroup($attribute, $subject, $token)
    {
        if ($subject instanceof MessageContext)
            $subject = $subject->getMessage();

        // Is this correct?
        if (!$subject instanceof Message) {
            return false;
        }

        // We are not at a point where we can decide group belongings at create.
        // (Bloody annoying, but should there be a post-entity build isGranted
        // call on create?
        if ($attribute == "create")
            return true;

        $user = $token->getUser();
        if (!method_exists($user, 'getGroupNames'))
            return false;

        if ($from = $subject->getFrom()) {
            if ($from_user = $this->sakonninMessages->getUserFromUserName($from)) {
                foreach($from_user->getGroupNames() as $gn) {
                    if (in_array($gn, $user->getGroupNames()))
                        return true;
                }
                
                if (in_array($from_user->getGroupNames(), $user->getGroupNames()))
                    return true;
            }
        }

        /*
         * Point here is to check if the object this message is about is in the
         * same group(s) as the use trying to mess with.
         */
        foreach ($subject->getContexts() as $context) {
            if ($object = $this->externalRetriever->getExternalDataFromContext($context)) {
                // The question now is. How do I know that the object is
                // what I am looking for?
                // For now, I presume it's within the same application and
                // do a pure match.
                if (!method_exists($object, 'getGroupNames'))
                    continue;
                // in_array does not work. And I don't trust php to not end up
                // with empty always be "false".
                if (!empty(array_intersect($object->getGroupNames(),
                        $user->getGroupNames())))
                    return true;
            }
        }
        return false;
    }
}
