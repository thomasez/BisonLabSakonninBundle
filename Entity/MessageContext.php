<?php

namespace BisonLab\SakonninBundle\Entity;

use BisonLab\CommonBundle\Entity\ContextBase;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * BisonLab\SakonninBundle\Entity\MessageContext
 *
 * @ORM\Table(name="MessageContext", uniqueConstraints={@ORM\UniqueConstraint(name="message_syst_obj_ext_idx", columns={"system", "object_name", "external_id"})})
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Entity\MessageContextRepository")
 * @Gedmo\Loggable
 */
class MessageContext extends ContextBase
{

    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="contexts")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false)
     */
    private $message;

    public function __construct($options = array()) {

        if (isset($options['message'])) 
            $this->setMessage($options['message']);

        parent::__construct($options);

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

    public function getOwnerClass()
    {
        return get_class($this->getMessage());
    }

    public function getOwnerEntity()
    {
        return "BisonLabSakonninBundle:Message";
    }

    public function isDeleteable()
    {
        return true;
    }
}
