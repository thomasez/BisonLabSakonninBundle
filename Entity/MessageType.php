<?php

namespace BisonLab\SakonninBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * MessageType
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Entity\MessageTypeRepository")
 * @Gedmo\Loggable
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="callback_function", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $callbackFunction;

    /**
     * @var string
     *
     * @ORM\Column(name="callback_type", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $callbackType;

    /**
     * @var string
     *
     * @ORM\Column(name="forward_function", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $forwardFunction;

    /**
     * @var string
     *
     * @ORM\Column(name="forward_type", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $forwardType;

    /**
     * @ORM\OneToMany(targetEntity="MessageType", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="MessageType", inversedBy="children")
     * @ORM\JoinColumn(name="parent_message_type_id", referencedColumnName="id")
     * @Gedmo\Versioned
     */
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="message_type", fetch="EXTRA_LAZY")
     **/
    private $messages;

    public function __construct($options = array())
    {
        $this->getMsgid(uniqid());
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
        return $this->callbackFunction;
    }

    /**
     * Set callbackType
     *
     * @param string $callbackType
     * @return MessageType
     */
    public function setCallbackType($callbackType)
    {
        $this->callbackType = $callbackType;

        return $this;
    }

    /**
     * Get callbackType
     *
     * @return string 
     */
    public function getCallbackType()
    {
        return $this->callbackType;
    }

    /**
     * Set forwardFunction
     *
     * @param string $forwardFunction
     * @return MessageType
     */
    public function setForwardFunction($forwardFunction)
    {
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
        return $this->forwardFunction;
    }

    /**
     * Set forwardType
     *
     * @param string $forwardType
     * @return MessageType
     */
    public function setForwardType($forwardType)
    {
        $this->forwardType = $forwardType;

        return $this;
    }

    /**
     * Get forwardType
     *
     * @return string 
     */
    public function getForwardType()
    {
        return $this->forwardType;
    }
}
