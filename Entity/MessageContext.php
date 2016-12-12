<?php

namespace BisonLab\SakonninBundle\Entity;

use BisonLab\CommonBundle\Entity\ContextBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * BisonLab\SakonninBundle\Entity\MessageContext
 *
 * @ORM\Table(name="sakonnin_messagecontext")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Entity\MessageContextRepository")
 */
class MessageContext
{
    use \BisonLab\CommonBundle\Entity\ContextBaseTrait;

    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="contexts")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false)
     */
    private $message;

    public function __construct($options = array())
    {
        if (isset($options['message'])) 
            $this->setMessage($options['message']);
        return $this->traitConstruct($options);
    }

    /** 
     * Set message
     *
     * @param object $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     *
     * @return object 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Generic main object setting.
     *
     * @return object 
     */
    public function setOwner($object)
    {
        return $this->setMessage($object);
    }

    /**
     * Generic main object.
     *
     * @return object 
     */
    public function getOwner()
    {
        return $this->getMessage();
    }

    public function getOwnerEntityAlias()
    {
        return "BisonLabSakonninBundle:Message";
    }

    public function isDeleteable()
    {
        return true;
    }
}
