<?php

namespace BisonLab\SakonninBundle\Service;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Controller\MessageController;

/**
 * Messages service.
 */
class Messages
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function postMessage($data, $context_data = array())
    {
        $em = $this->getDoctrineManager();
        $message = null;
        if ($data instanceof Message) {
            $message = $data;
        } else {
            $message = new Message($data);
            if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                    $message->setMessageType($message_type);            
            } else {
                throw new \InvalidArgumentException("No message type found or set.");
            }

            if (isset($context_data)
                && isset($context_data['system'])
                && isset($context_data['object_name'])
                && isset($context_data['external_id'])) {

                $message_context = new MessageContext($context_data);
                $message->addContext($message_context);
                $em->persist($message_context);
            }

            if (isset($data['in_reply_to'])) {
                if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $data['in_reply_to']))) {
                    return false;
                } else {
                    $message->setInReplyTo($reply_to);
                }
            }
            if (isset($data['from_type'])) {
                $message->setFromType($data['from_type']);
            } else {
                throw new \InvalidArgumentException("No from address type found or set.");
            }

            if (isset($data['from'])) {
                $message->setFrom($data['from']);
            } else {
                // To be considered.
                // throw new \InvalidArgumentException("No from found or set.");
            }

            if (isset($data['to_type']))
                $message->setToType($data['to_type']);
        }

        // All this is a hack, but it's gotta be somewhere and this works 
        // for now.
        if ($message->getToType() == "INTERNAL") {
            $message->setState("UNREAD");
            // Add the To-user object as a receiver.
            $message->addReceiver($message->getTo());
        } else {
            // Gotta have something.
            $message->setState("SENT");
        }
        $em->persist($message);

        // I planned to use an event listener to dispatch callback/forward
        // functions, but why? This postMessage functions shall be the only
        // entry point for creating messages, so why should I bother?
        $message->setSender($this->getLoggedInUser());
        $dispatcher = $this->container->get('sakonnin.functions');
        $dispatcher->dispatchMessageFunctions($message);

        $em->flush();
        return $message;
    }

    public function getCreateForm($options = array())
    {
        $em = $this->getDoctrineManager();
        $message = null;
        $message_context = null;
        if (isset($options['message']) && $options['message'] instanceof Message) {
             $message =  $options['message'];
        } elseif (isset($options['message_data']) && $data = $options['message_data']) {
            if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                $data['message_type'] = $message_type;
            }
            $message = new Message($data);
        } else {
            $message = new Message();
        }

        if (isset($options['message_context'])) {
            $message_context = new MessageContext($options['message_context']);
            $message->addContext($message_context);
        }

        // What does the form say?
        if (isset($options['message_data']['in_reply_to'])) {
            if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $options['message_data']['in_reply_to']))) {
                return false;
            } else {
                $message->setInReplyTo($reply_to);
            }
        }

        $c = new MessageController();
        $c->setContainer($this->container);

        $form = $c->createCreateForm($message);

        // You may wonder why. It's beause this one is called from twig
        // templates as well as the message controller (Which adds stuff).
        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    public function getCreatePmForm($options = array())
    {
        $message = new Message();
        // What does the form say?
        if (isset($options['message_data']['in_reply_to'])) {
            if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $options['message_data']['in_reply_to']))) {
                return false;
            } else {
                $message->setInReplyTo($reply_to);
            }
        }

        $c = new MessageController();
        $c->setContainer($this->container);

        $form = $c->createCreatePmForm($message);

        // You may wonder why. It's beause this one is called from twig
        // templates as well as the message controller (Which adds stuff).
        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    /*
     * Get and list messsag(es) functions.
     */
    public function getMessagesForLoggedIn($state = null)
    {
        $user = $this->getLoggedInUser();
        return $this->getMessagesForUser($user, $state);
    }

    public function getMessagesForUser($user, $state = null)
    {
        $user = $this->getLoggedInUser();

        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:Message');
        $query = $repo->createQueryBuilder('m')
            ->where('m.from = :userid')
            ->orWhere('m.to = :userid');
        if ($state)
            $query->andWhere("m.state = :state")
                ->setParameter('state', $state);

        $query->setParameter('userid', $user->getId());
        return $query->getQuery()->getResult();
    }

    public function contextHasMessages($context)
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:MessageContext');
        return $repo->contextHasMessages($context);
    }

    /*
     * Helper functions.
     */
    public function getUserNameFromUserId($userid)
    {
        // It may just not be an ID.
        if (!is_numeric($userid)) return $userid;
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$userid));
        if (!$user) return $userid;
        return $user->getUserName();;
    }

    public function getEmailFromUser($user = null)
    {
        if (!$user)
            $user = $this->getLoggedInUser();
        // It may just be an ID.
        if (is_numeric($user)) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array('id' => $user));
        }
        // Or string?
        if (is_string($user)) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array('username' => $user));
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
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array('id' => $user));
        }
        // Or string?
        if (is_string($user)) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array('username' => $user));
        }

        if (is_object($user) && method_exists($user, 'getMobilePhoneNumber'))
            return $user->getMobilePhoneNumber();
        if (is_object($user) && method_exists($user, 'getPhoneNumber'))
            return $user->getPhoneNumber();
        return null;
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

    public function getMessageType($name)
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:MessageType');
        return $repo->findOneByName($name);
    }
}
