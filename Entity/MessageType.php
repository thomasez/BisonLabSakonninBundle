<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageType
 *
 * @ORM\Table(name="sakonnin_messagetype")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Entity\MessageTypeRepository")
 */
class MessageType
{
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * Yes, there is only one. I am not sure this is enough and to be honest,
     * why I chose to make it only one. Keep it simple I guess.
     *
     * And BTW; How do you know which function the attributes are for?
     * Definately adding complexity by adding more than one function.
     * @var string
     *
     * @ORM\Column(name="callback_function", type="string", length=255, nullable=true)
     */
    private $callbackFunction;

    /**
     * @var string
     *
     * @ORM\Column(name="callback_attributes", type="json_array", length=255, nullable=true)
     */
    private $callbackAttributes = array();

    /**
     * Same here, this could have been an array, but it's complicated.
     * @var string
     *
     * @ORM\Column(name="forward_function", type="string", length=255, nullable=true)
     */
    private $forwardFunction;

    /**
     * @var string
     *
     * @ORM\Column(name="forward_attributes", type="json_array", length=255, nullable=true)
     */
    private $forwardAttributes = array();

    /**
     * @var int
     *
     * @ORM\Column(name="expunge_days", type="integer")
     */
    private $expunge_days = 0;

    /* This is a tree structure while in the UI it's just to show a group.
     * I've chosen to do this in case of more advanced functionality is needed
     * one day.
     */
    /**
     * @ORM\OneToMany(targetEntity="MessageType", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="MessageType", inversedBy="children")
     * @ORM\JoinColumn(name="parent_message_type_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="message_type", fetch="EXTRA_LAZY")
     **/
    private $messages;

    public function __construct($options = array())
    {
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }
    
    /*
     * Automatically generated getters and setters below this
     */
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
     * Set name
     *
     * @param string $name
     * @return MessageType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return MessageType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set callbackFunction
     *
     * @param string $callbackFunction
     * @return MessageType
     */
    public function setCallbackFunction($callbackFunction)
    {
        // If it's the same as any thing in the chain, drop it.
        if ($callbackFunction == $this->getCallbackFunction()) {
            return $this;
        }
        $this->callbackFunction = $callbackFunction;

        return $this;
    }

    /**
     * Get callbackFunction
     *
     * @return string 
     */
    public function getCallbackFunction()
    {
        if ($this->callbackFunction)
            return $this->callbackFunction;
        if ($this->getParent())
            return $this->getParent()->getCallbackFunction();
        return null;
    }

    /**
     * Set callbackAttributes
     *
     * @param string $callbackAttributes
     * @return MessageType
     */
    public function setCallbackAttributes($callbackAttributes)
    {
        // If it's the same as any thing in the chain, drop it.
        if ($callbackAttributes == $this->getCallbackAttributes()) {
            return $this;
        }
        $this->callbackAttributes = $callbackAttributes;

        return $this;
    }

    /**
     * Get callbackAttributes
     *
     * @return string 
     * I am not gonna merge the attributes with the parents attributes  here.
     * That will just be too messy.
     */
    public function getCallbackAttributes()
    {
        if ($this->callbackAttributes)
            return $this->callbackAttributes;
        if ($this->getParent())
            return $this->getParent()->getCallbackAttributes();
        return null;
    }

    /**
     * Set forwardFunction
     *
     * @param string $forwardFunction
     * @return MessageType
     */
    public function setForwardFunction($forwardFunction)
    {
        // If it's the same as any thing in the chain, drop it.
        if ($forwardFunction == $this->getForwardFunction()) {
            return $this;
        }
        $this->forwardFunction = $forwardFunction;

        return $this;
    }

    /**
     * Get forwardFunction
     *
     * @return string 
     */
    public function getForwardFunction()
    {
        if ($this->forwardFunction)
            return $this->forwardFunction;
        if ($this->getParent())
            return $this->getParent()->getForwardFunction();
        return null;
    }

    /**
     * Set forwardAttributes
     *
     * @param string $forwardAttributes
     * @return MessageType
     */
    public function setForwardAttributes($forwardAttributes)
    {
        // If it's the same as any thing in the chain, drop it.
        if ($forwardAttributes == $this->getForwardAttributes()) {
            return $this;
        }
        $this->forwardAttributes = $forwardAttributes;

        return $this;
    }

    /**
     * Get forwardAttributes
     *
     * @return string 
     * I am not gonna merge the attributes with the parents attributes  here.
     * That will just be too messy.
     */
    public function getForwardAttributes()
    {
        if ($this->forwardAttributes)
            return $this->forwardAttributes;
        if ($this->getParent())
            return $this->getParent()->getForwardAttributes();
        return null;
    }

    /**
     * Add child
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $child
     * @return MessageType
     */
    public function addChild(\BisonLab\SakonninBundle\Entity\MessageType $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove children
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $child
     */
    public function removeChild(\BisonLab\SakonninBundle\Entity\MessageType $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $parent
     * @return MessageType
     */
    public function setParent(\BisonLab\SakonninBundle\Entity\MessageType $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \BisonLab\SakonninBundle\Entity\MessageType 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add messages
     *
     * @param \BisonLab\SakonninBundle\Entity\Message $messages
     * @return MessageType
     */
    public function addMessage(\BisonLab\SakonninBundle\Entity\Message $messages)
    {
        $this->messages[] = $messages;

        return $this;
    }

    /**
     * Remove messages
     *
     * @param \BisonLab\SakonninBundle\Entity\Message $messages
     */
    public function removeMessage(\BisonLab\SakonninBundle\Entity\Message $messages)
    {
        $this->messages->removeElement($messages);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set expunge_days
     *
     * @param integer $expungeDays
     * @return MessageType
     */
    public function setExpungeDays($expungeDays)
    {
        $this->expunge_days = $expungeDays;

        return $this;
    }

    /**
     * Get expunge_days
     *
     * @return integer 
     */
    public function getExpungeDays()
    {
        return $this->expunge_days;
    }
}
