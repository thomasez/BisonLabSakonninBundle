<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

/**
 * MessageType
 * TODO: This will probably become MesageGroup or something like it.
 *       Why? I added "Type" here..
 *       Which is "Base Type" for now.
 *
 * @ORM\Table(name="sakonnin_messagetype")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\MessageTypeRepository")
 */
class MessageType
{
    /*
     * Why are they here and not in ExternalConfig thingie, aka types.yml?
     * Good question.
     * The answer is that this is programmed in and extending it has to be
     * programmed into the system itself, in many places.
     * Most others does not have to.
     */
    private static $security_models = array(
        // Maybe not the best word for it, but is User, Sender and Receiver.
        'PRIVATE' => array('short' => 'Private', 'description' => 'User/Sender and receiver can read.'),
        // Everyone can read.
        'ALL_READ' => array('short' => 'All read', 'description' => 'Everyone can read, admin can write.'),
        // Everyone can read and write
        'ALL_READWRITE' => array('short' => 'All read write', 'description' => 'Everyone can read and write.'),
        // Only Admins can read and write.
        'ADMIN_ONLY' => array('short' => 'Admin only', 'description' => 'Only Admin can read and write.'),
        'ADMIN_RW_USER_R' => array('short' => 'Admin read and write, object read', 'description' => 'Only Admin can write and the object (user) can read.'),
        'ADMIN_RW_USER_RW' => array('short' => 'Admin, user read and write', 'description' => 'Admin and user can read and write')
    );

    private static $expunge_methods = array(
        'DELETE' => array('short' => 'Delete', 'description' => 'Message is deleted.'),
        'ARCHIVE' => array('short' => 'Archive', 'description' => 'Message is marked as archived.'),
    );

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
     * @var string $base_type
     *
     * @ORM\Column(name="base_type", type="string", length=50, nullable=true)
     * @Assert\Choice(callback = "getBaseTypes")
     */
    private $base_type;

    /**
     * Yes, there is only one. I am not sure this is enough and to be honest,
     * why I chose to make it only one. Keep it simple I guess.
     *
     * And BTW; How do you know which function the attributes are for?
     * Definately adding complexity by adding more than one function.
     * @var string
     *
     * @ORM\Column(name="security_model", type="string", length=40, nullable=false, options={"default"="PRIVATE"})
     */
    private $security_model;

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

    /**
     * @var string
     *
     * @ORM\Column(name="expunge_method", type="string", length=10, nullable=false, options={"default"="DELETE"})
     */
    private $expunge_method;

    /**
     * @var string
     *
     * @ORM\Column(name="expire_method", type="string", length=10, nullable=false, options={"default"="DELETE"})
     */
    private $expire_method;

    /**
     * @ORM\ManyToOne(targetEntity="SakonninTemplate", inversedBy="message_types")
     * @ORM\JoinColumn(name="sakonnin_template_id", referencedColumnName="id", nullable=true)
     **/
    private $sakonnin_template;

    /* This is a tree structure while in the UI it's just to show a group.
     * I've chosen to do this in case of more advanced functionality is needed
     * one day.
     */
    /**
     * @ORM\OneToMany(targetEntity="MessageType", mappedBy="parent", fetch="EAGER", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
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
     * Set base_type
     *
     * @param string $base_type
     * @return Site
     */
    public function setBaseType($base_type)
    {
        $base_type = strtoupper($base_type);
        if (!in_array($base_type, self::getBaseTypes())) {
            throw new \InvalidArgumentException(sprintf('The "%s" base type is not a valid base type.', $base_type));
        }
        $this->base_type = $base_type;
        return $this;
    }

    /**
     * Get base_type
     *
     * @return string 
     */
    public function getBaseType()
    {
        if ($this->base_type)
            return $this->base_type;
        if ($this->getParent())
            return $this->getParent()->getBaseType();
        return null;
    }

    /**
     * Get base_types
     *
     * @return array 
     */
    public static function getBaseTypes()
    {
        return array_keys(ExternalEntityConfig::getBaseTypes());
    }

    public static function getBaseTypesAsChoices()
    {
        $bases = array();
        foreach (ExternalEntityConfig::getBaseTypes() as $name => $bt) {
            $bases[$bt['short']] = $name;
        }
        return $bases;
    }

    /**
     * Set security_model
     *
     * @param string $security_model
     * @return Site
     */
    public function setSecurityModel($security_model)
    {
        $security_model = strtoupper($security_model);
        if (!isset(self::getSecurityModels()[$security_model])) { 
            throw new \InvalidArgumentException(sprintf('The "%s" security model is not a valid security model.', $security_model));
        }
        $this->security_model = $security_model;
        return $this;
    }

    /**
     * Get security_model
     *
     * @return string 
     */
    public function getSecurityModel()
    {
        if ($this->security_model)
            return $this->security_model;
        elseif ($this->getParent())
            return $this->getParent()->getSecurityModel();
        else
            return null;
    }

    /**
     * Get security_models
     *
     * @return array 
     */
    public static function getSecurityModels()
    {
        // I could do the external config trick here aswell but I'd rather not
        // have so many security_models.
        return self::$security_models;
    }

    public static function getSecurityModelsAsChoices()
    {
        $mods = array();
        foreach (self::$security_models as $name => $sm) {
            $mods[$sm['short']] = $name;
        }
        return $mods;
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
    public function getMessages($include_children = false)
    {
        $messages = $this->messages->toArray();
        if ($include_children) {
            foreach ($this->children as $child) {
                $messages = array_merge($messages, $child->getMessages(true));
            }
            return $messages;
        }
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

    /**
     * Set expunge_method
     *
     * @param string $expunge_method
     * @return Site
     */
    public function setExpungeMethod($expunge_method)
    {
        $expunge_method = strtoupper($expunge_method);
        if (!isset(self::getExpungeMethods()[$expunge_method])) { 
            throw new \InvalidArgumentException(sprintf('The "%s" expunge method is not a valid expunge method.', $expunge_method));
        }
        $this->expunge_method = $expunge_method;
        return $this;
    }

    /**
     * Get expunge_method
     *
     * @return string 
     */
    public function getExpungeMethod()
    {
        return $this->expunge_method;
    }

    /**
     * Get expunge_methods
     *
     * @return array 
     */
    public static function getExpungeMethods()
    {
        // I could do the external config trick here aswell but I'd rather not
        // have so many expunge_methods.
        return self::$expunge_methods;
    }

    public static function getExpungeMethodsAsChoices()
    {
        $mods = array();
        foreach (self::$expunge_methods as $name => $sm) {
            $mods[$sm['short']] = $name;
        }
        return $mods;
    }

    /**
     * Set expire_method
     *
     * @param string $expire_method
     * @return Site
     */
    public function setExpireMethod($expire_method)
    {
        $expire_method = strtoupper($expire_method);
        if (!isset(self::getExpungeMethods()[$expire_method])) { 
            throw new \InvalidArgumentException(sprintf('The "%s" expire method is not a valid expire method.', $expire_method));
        }
        $this->expire_method = $expire_method;
        return $this;
    }

    /**
     * Get expire_method
     *
     * @return string 
     */
    public function getExpireMethod()
    {
        return $this->expire_method;
    }

    /**
     * Set sakonnin_template
     *
     * @param \BisonLab\SakonninBundle\Entity\SakonninTemplate $messageType
     * @return Message
     */
    public function setSakonninTemplate(\BisonLab\SakonninBundle\Entity\SakonninTemplate $sakonninTemplate = null)
    {
        $this->sakonnin_template = $sakonninTemplate;

        return $this;
    }

    /**
     * Get sakonnin_template
     *
     * @return \BisonLab\SakonninBundle\Entity\SakonninTemplate
     */
    public function getSakonninTemplate()
    {
        return $this->sakonnin_template;
    }

    public function getFirstState()
    {
        if (!$bt = $this->getBaseType())
            return null;
        return ExternalEntityConfig::getBaseTypes()[$bt]['states'][0];
    }
}
