<?php

namespace BisonLab\SakonninBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

use Doctrine\ORM\Mapping as ORM;

use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

/**
 * Message
 *
 * Mainly coherent with RFC 5322. When extending functionality we SHOULD comply
 * with the RFC where it adresses the functionality.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Entity\MessageRepository")
 */
class Message
{
    use TimestampableEntity;
    use BlameableEntity;

    private static $states = array('SENDING', 'UNREAD', 'SENT', 'READ');

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="message_id", type="string", length=100, nullable=false, unique=true)
     */
    private $message_id;

    /**
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="replies")
     * @ORM\JoinColumn(name="in_reply_to_message_id", referencedColumnName="id")
     */
    protected $in_reply_to;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="in_reply_to", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $replies;

    /**
     * @var string
     *
     * @ORM\Column(name="`from`", type="string", length=255, nullable=true)
     */
    private $from;

    /**
     * @var string $from_type
     *
     * @ORM\Column(name="from_type", type="string", length=40, nullable=false)
     */
    private $from_type;

    /**
     * @var string
     *
     * @ORM\Column(name="`to`", type="string", length=255, nullable=true)
     */
    private $to;

    /**
     * @var string $to_type
     *
     * @ORM\Column(name="to_type", type="string", length=40, nullable=true)
     */
    private $to_type;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="text", nullable=true)
     */
    private $content_type;

    /**
     * @var string
     *
     * @ORM\Column(name="header", type="text", nullable=true)
     */
    private $header;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=50, nullable=true)
     * @Assert\Choice(callback = "getStates")
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="MessageType", inversedBy="messages")
     * @ORM\JoinColumn(name="message_type_id", referencedColumnName="id", nullable=false)
     **/
    private $message_type;

    /**
     * @ORM\OneToMany(targetEntity="MessageContext", mappedBy="message", cascade={"persist", "remove"})
     */
    private $contexts;

    public function __construct($options = array())
    {
        $this->setMessageId(uniqid());
        if (isset($options['from']) ) {
            $this->setFrom($options['from']);
        }
        if (isset($options['to']) ) {
            $this->setTo($options['to']);
        }
        if (isset($options['subject']) ) {
            $this->setSubject($options['subject']);
        }
        if (isset($options['content_type']) ) {
            $this->setContentType($options['content_type']);
        }
        if (isset($options['header']) ) {
            $this->setHeader($options['header']);
        }
        if (isset($options['body']) ) {
            $this->setBody($options['body']);
        }
        if (isset($options['message_type']) && $options['message_type'] instanceof \BisonLab\SakonninBundle\Entity\MessageType ) {
            $this->setMessageType($options['message_type']);
        }
        if (isset($options['in_reply_to']) && $options['in_reply_to'] instanceof \BisonLab\SakonninBundle\Entity\Message ) {
            $this->setInReply($options['in_reply_to']);
        }

        $this->replies  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contexts = new \Doctrine\Common\Collections\ArrayCollection();

    }

    public function __toString()
    {
        return $this->message_id;
    }

    public function getOrigDate()
    {
        return $this->createdat;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message_id
     *
     * @param string $messageId
     * @return Message
     */
    public function setMessageId($messageId)
    {
        $this->message_id = $messageId;

        return $this;
    }

    /**
     * Get message_id
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * Set from
     *
     * @param string $from
     * @return Message
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set from_type
     *
     * @param string $from_type
     * @return Message
     */
    public function setFromType($from_type)
    {
        if ($from_type == $this->from_type) return $this;
        $from_type = strtoupper($from_type);
        if (!isset(self::getAddressTypes()[$from_type])) {
            throw new \InvalidArgumentException(sprintf('The "%s" from_type is not a valid address type.', $from_type));
        }

        $this->from_type = $from_type;
        return $this;
    }

    /**
     * Get from_type
     * @return string
     */
    public function getFromType()
    {
        return $this->from_type;
    }

    /**
     * Set to
     *
     * @param string $to
     * @return Message
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set to_type
     *
     * @param string $to_type
     * @return Message
     */
    public function setToType($to_type)
    {
        if ($to_type == $this->to_type) return $this;
        $to_type = strtoupper($to_type);
        if (!isset(self::getAddressTypes()[$to_type])) {
            throw new \InvalidArgumentException(sprintf('The "%s" to_type is not a valid address type.', $to_type));
        }
        $this->to_type = $to_type;
        return $this;
    }

    /**
     * Get to_type
     * @return string
     */
    public function getToType()
    {
        return $this->to_type;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set contentType
     *
     * @param string $contentType
     * @return Message
     */
    public function setContentType($contentType)
    {
        $this->content_type = $contentType;

        return $this;
    }

    /**
     * Get contentType
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Set header
     *
     * @param string $header
     * @return Message
     */
    public function setHeader($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Get header
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return Message
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set in_reply_to
     *
     * @param \BisonLab\SakonninBundle\Entity\Message $inReplyTo
     * @return Message
     */
    public function setInReplyTo(\BisonLab\SakonninBundle\Entity\Message $inReplyTo = null)
    {
        $this->in_reply_to = $inReplyTo;

        return $this;
    }

    /**
     * Get in_reply_to
     *
     * @return \BisonLab\SakonninBundle\Entity\Message
     */
    public function getInReplyTo()
    {
        return $this->in_reply_to;
    }

    /**
     * Add replies
     *
     * @param \BisonLab\SakonninBundle\Entity\Message $replies
     * @return Message
     */
    public function addReply(\BisonLab\SakonninBundle\Entity\Message $replies)
    {
        $this->replies[] = $replies;

        return $this;
    }

    /**
     * Remove replies
     *
     * @param \BisonLab\SakonninBundle\Entity\Message $replies
     */
    public function removeReply(\BisonLab\SakonninBundle\Entity\Message $replies)
    {
        $this->replies->removeElement($replies);
    }

    /**
     * Get replies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReplies()
    {
        return $this->replies;
    }

    /**
     * Set message_type
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $messageType
     * @return Message
     */
    public function setMessageType(\BisonLab\SakonninBundle\Entity\MessageType $messageType = null)
    {
        $this->message_type = $messageType;

        return $this;
    }

    /**
     * Get message_type
     *
     * @return \BisonLab\SakonninBundle\Entity\MessageType
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Site
     */
    public function setState($state)
    {
        if (is_int($state)) { $state = self::getStates()[$state]; }
        $state = strtoupper($state);
        if (!in_array($state, self::getStates())) {
            throw new \InvalidArgumentException(sprintf('The "%s" state is not a valid state.', $state));
        }
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get states
     *
     * @return array 
     */
    public static function getStates()
    {
        // I could do the external config trick here aswell but I'd rather not
        // have so many states.
        return self::$states;
    }

    /**
     * Get contexts
     *
     * @return objects
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * Add contexts
     *
     * @param BisonLab\SakonninBundle\Entity\MessageContext $context;
     * @return Message
     */
    public function addContext(\BisonLab\SakonninBundle\Entity\MessageContext $context)
    {
        $this->contexts[] = $context;
        $context->setOwner($this);
        return $this;
    }

    /**
     * Remove context
     *
     * @param BisonLab\SakonninBundle\Entity\MessageContext $context;
     */
    public function removeContext(\BisonLab\SakonninBundle\Entity\MessageContext $context)
    {
        $this->contexts->removeElement($context);
    }

    /**
     * Get Address Types (For use by FromType and ToType)
     *
     * @return array
     */
    public static function getAddressTypes()
    {
        return ExternalEntityConfig::getAddressTypes();
    }

    /* Wonder why I only ask for the newest message and not the oldest? 
     * You should not..
     */
    public function getNewestInThread()
    {
        $newest = $this;
        $next_reply = function ($message) use (&$next_reply, &$newest)
        {
            if ($message->getReplies()) {
                foreach ($message->getReplies() as $r) {
                    $next_reply($r);
                }
            }
            if ($message->getCreatedAt() > $newest->getCreatedAt()) {
                $newest = $message;
            }
        };

        $next_reply($this);
        return $newest;
    }

    /**
     * Get First message in Thread.
     * (And no, I could not resist the function name.)
     *
     * @return \BisonLab\SakonninBundle\Entity\Message
     */
    public function getFirstPost()
    {
        if (!$this->getInReplyTo())
            return $this;
        else
            return $this->getInReplyTo();
    }

    /* 
     * Instead of adding serializer and so on.
     */
    public function __toArray()
    {
        return array(
            'subject' => $this->getSubject(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'createdat' => $this->getCreatedAt(),
            'in_reply_to' => $this->getInReplyTo()->getMessageId(),
            'message_type' => (string)$this->getMessageType(),
            'body' => $this->getBody(),
        );
    }

}
