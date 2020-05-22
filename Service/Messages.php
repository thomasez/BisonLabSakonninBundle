<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;
use BisonLab\SakonninBundle\Controller\MessageController;

/**
 * Messages service.
 *
 * TODO: Remove the userstuff from here. Lots of it is in commonbundle now.
 */
class Messages
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;
    private $user_repository;

    public function __construct($container)
    {
        $this->container  = $container;
        $this->stemplates = $container->get('sakonnin.templates');
    }

    public function postMessage($data, $context_data = [])
    {
        $em = $this->getDoctrineManager();
        $message = null;
        if ($data instanceof Message) {
            $message = $data;
        } else {
            $message = new Message($data);
            if (isset($data['template'])) {
                $template_data = $data['template_data'];
                $template_data['user'] = $this->getLoggedInUser();
                if (!$template = $this->stemplates->getTemplate($data['template']))
                    throw new \InvalidArgumentException("There is no template named " . $data['template']);
                $message->setBody($this->stemplates->parse($template->getTemplate(), $template_data));
            }

            if (isset($data['message_type']) 
                    && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
                $message->setMessageType($message_type);            
            } else {
                throw new \InvalidArgumentException("No message type found or set.");
            }

            if (!empty($context_data)) {
                /*
                 * If only one context and it's not in it's own array (BC),
                 * it has to become one.
                 */
                if (isset($context_data['system'])) {
                    // NO messing with refrerences.. (future proof?)
                    $c['system'] = $context_data['system'];
                    $c['object_name'] = $context_data['object_name'];
                    $c['external_id'] = $context_data['external_id'];
                    $context_data = [$c];
                }
                foreach ($context_data as $context) {
                    // TODO: Start getting rid of isset.
                    if (isset($context['system'])
                        && isset($context['object_name'])
                        && isset($context['external_id'])) {
                            $message_context = new MessageContext($context);
                            $message->addContext($message_context);
                            $em->persist($message_context);
                    }
                }
            }

            if (isset($data['in_reply_to'])) {
                if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:Message')->findOneBy(array('message_id' => $data['in_reply_to']))) {
                    return false;
                } else {
                    $message->setInReplyTo($reply_to);
                }
            }

            // Get a default one?
            if (isset($data['from_type'])) {
                $message->setFromType($data['from_type']);
            } else {
                throw new \InvalidArgumentException("No from address type found or set.");
            }

            if (isset($data['state'])) {
                $message->setState($data['state']);
            }

            // Dependant on what this is.
            if (isset($data['from'])) {
                $message->setFrom($data['from']);
            }

            if (isset($data['to_type']))
                $message->setToType($data['to_type']);
        }

        // All this is a hack, but it's gotta be somewhere and this works 
        // for now.
        if ($message->getToType() == "INTERNAL") {
            // Gotta be able to have multiple receivers/to.
            $toers = [];
            if (preg_match("/,/", $message->getTo())) {
                $toers = explode(",", $message->getTo());
            } elseif (!empty($message->getTo())) {
                $toers = [$message->getTo()];
            } else {
                /*
                 * This is very half way. It takes for granted that the
                 * external_id is a userid from within the system. Which is
                 * kinda OK since this is the message type INTERNAL :=)
                 */
                foreach ($message->getContexts() as $context) {
                    $toers[] = $context->getExternalId();
                }
            }
            if (empty($toers))
                return true;
            foreach ($toers as $toer) {
                // In case of no userid, but username
                // (Gotta consider some more automagic handling of all this.)
                // (And is_numeric is kinda wrong since a user can be named
                // "666")
                if (!is_numeric($toer)) {
                    if ($touser = $this->getUserFromUserName($toer)) {
                        $message->setTo($touser->getId());
                    } else {
                        throw new \InvalidArgumentException("No user with that username.(" . $toer . ")");
                    }
                }
                // It's the state for the receivers, not sender as long as
                // this is an INTERAL to-type.
                $message->setFirstState();
                $message->addReceiver($this->getUserFromUserId($toer));
            }
            // Add the To-user object as a receiver.
        } else {
            // Gotta have something.
            if (!$message->getState())
                $message->setFirstState();
        }
        $em->persist($message);

        // I planned to use an event listener to dispatch callback/forward
        // functions, but why? This postMessage functions shall be the only
        // entry point for creating messages, so why should I bother?
        $message->setSender($this->getLoggedInUser());
        $dispatcher = $this->container->get('sakonnin.functions');
        $dispatcher->dispatchMessageFunctions($message, $data);

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

    public function getEditForm($message, $create_view = false)
    {
        $c = new MessageController();
        $c->setContainer($this->container);
        $form = $c->createEditForm($message);
        if ($create_view)
            return $form->createView();
        else
            return $form;
    }

    public function getCreateDeleteForm($message, $create_view = false)
    {
        $c = new MessageController();
        $c->setContainer($this->container);
        $form = $c->createDeleteForm($message);
        if ($create_view)
            return $form->createView();
        else
            return $form;
    }

    /*
     * Get and list messsag(es) functions.
     */
    public function getMessagesForContext($criterias)
    {
        if (!isset($criterias['context'])) {
            $criterias['context'] = [
                'system' => $criterias['system'],
                'object_name' => $criterias['object_name'],
                'external_id' => $criterias['external_id'],
                ];
        }
        return $this->getMessages($criterias);
    }

    public function getMessagesForLoggedIn($criterias = array())
    {
        $user = $this->getLoggedInUser();
        return $this->getMessagesForUser($user, $criterias);
    }

    public function getMessagesForUser($user, $criterias = array())
    {
        $criterias['userid'] = $user->getId();
        $criterias['username'] = $user->getUsername();
        return $this->getMessages($criterias);
    }

    public function getMessages($criterias = array())
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:Message');

        // There can be only one
        if (isset($criterias['id'])) {
            return $repo->find($criterias['id']);
        }
        if (isset($criterias['message_id'])) {
            return $repo->findOneBy(['message_id' => $criterias['message_id']]);
        }
        $query = $repo->createQueryBuilder('m');

        if (isset($criterias['context'])) {
            $system      = $criterias['context']['system'];
            $object_name = $criterias['context']['object_name'];
            $external_id = $criterias['context']['external_id'];
            $query = $em->createQueryBuilder();
            $query->select('m')
                ->from('BisonLabSakonninBundle:Message', 'm')
                ->leftJoin('m.contexts', 'mc')
                ->where("mc.system = :system")
                ->andWhere("mc.object_name = :object_name")
                ->andWhere("mc.external_id = :external_id")
                ->setParameter('system', $system)
                ->setParameter('object_name', $object_name)
                ->setParameter('external_id', $external_id);
        }

        if (isset($criterias['userid'])) {
            $query->andWhere('m.to in (:userid, :username)');
            if (isset($criterias['include_from']))
                $query->orWhere('m.from in (:userid, :username)');
            $query->setParameter('userid', $criterias['userid']);
            $query->setParameter('username', $criterias['username']);
        }

        if (isset($criterias['state'])) {
            $query->andWhere("m.state = :state")
                ->setParameter('state', $criterias['state']);
        }

        if (isset($criterias['states'])) {
            $query->andWhere("m.state in (:states)")
                ->setParameter('states', $criterias['states']);
        }

        if (isset($criterias['base_type'])) {
            $types = $this->getMessageTypes(['base_type' => $criterias['base_type']]);
            $query->andWhere("m.message_type in (:message_types)")
                ->setParameter('message_types', $types);
        }

        if (isset($criterias['message_type'])) {
            $mt = $this->getMessageType($criterias['message_type']);
            $query->andWhere("m.message_type = :message_type")
                ->setParameter('message_type', $mt);
        }

        if (isset($criterias['message_types'])) {
            $types = [];
            foreach ($criterias['message_types'] as $mt) {
                $types[] = $this->getMessageType($mt);
            }
            $query->andWhere("m.message_type in (:message_types)")
                ->setParameter('message_types', $types);
        }

        if (isset($criterias['message_group'])) {
            $mg = $this->getMessageType($criterias['message_group']);
            $types = $mg->getChildren();
            $types->add($mg);
            $query->andWhere("m.message_type in (:message_types)")
                ->setParameter('message_types', $types);
        }

        if (isset($criterias['not_message_type'])) {
            $mt = $this->getMessageType($criterias['not_message_type']);
            $query->andWhere("m.message_type != :message_type")
                ->setParameter('message_type', $mt);
        }

        if (!isset($criterias['with_replies'])) {
            $query->andWhere("m.in_reply_to is null");
        }

        if (isset($criterias['not_message_group'])) {
            $mg = $this->getMessageType($criterias['not_message_group']);
            $types = $mg->getChildren();
            $types->add($mg);
            $query->andWhere("m.message_type not in (:message_types)")
                ->setParameter('message_types', $types);
        }

        if (isset($criterias['order'])) {
            $query->orderBy("m.createdAt", $criterias['order']);
        } else {
            $query->orderBy("m.createdAt", "ASC");
        }
        return $query->getQuery()->getResult();
    }

    public function contextHasMessages($context, $with_contexts = false)
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:MessageContext');
        return $repo->contextHasMessages($context, $with_contexts);
    }

    /*
     * Helper functions.
     */
}
