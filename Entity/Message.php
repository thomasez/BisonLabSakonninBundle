<?php

namespace BisonLab\SakonninBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\OneToMany(targetEntity="Message", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $replies;

    /**
     * @var string
     *
     * @ORM\Column(name="from", type="string", length=255, nullable=true)
     */
    private $from;

    /**
     * @var string
     *
     * @ORM\Column(name="to", type="string", length=255, nullable=true)
     */
    private $to;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="text", nullable=true)
     */
    private $contentType;

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
     * @ORM\ManyToOne(targetEntity="MessageType", inversedBy="messages")
     * @ORM\JoinColumn(name="message_type_id", referencedColumnName="id")
     **/
    private $message_type;

    /**
     * @ORM\OneToMany(targetEntity="MessageContext", mappedBy="message", cascade={"persist", "remove"})
     */
    private $contexts;

    public function __construct($options = array())
    {
        $this->setMessageId(uniqid());
        $this->replies  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contexts = new \Doctrine\Common\Collections\ArrayCollection();

    }

    public function __toString()
    {
        return $this->message_id;
    }

    public function getOrigDate()
    {
        return $this->created_at;
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
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get contentType
     *
     * @return string 
     */
    public function getContentType()
    {
        return $this->contentType;
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
     * @param NTE\IpamBundle\Entity\NumberContext $contexts
     * @return Number
     */
    public function addContext(\NTE\IpamBundle\Entity\NumberContext $contexts)
    {
        $this->contexts[] = $contexts;
        return $this;
    }

    /**
     * Remove contexts
     *
     * @param NTE\IpamBundle\Entity\NumberContext $contexts
     */
    public function removeContext(\NTE\IpamBundle\Entity\NumberContext $contexts)
    {
        $this->contexts->removeElement($contexts);
    }

}
