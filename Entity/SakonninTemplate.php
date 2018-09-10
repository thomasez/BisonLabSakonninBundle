<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SakonninTemplate
 *
 * @ORM\Table(name="sakonnin_template")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\SakonninTemplateRepository")
 */
class SakonninTemplate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="lang_code", type="string", length=7)
     */
    private $lang_code;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="text")
     */
    private $template;

    /**
     * @ORM\OneToMany(targetEntity="MessageType", mappedBy="templates", fetch="EXTRA_LAZY")
     **/
    private $message_types;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return SakonninTemplate
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
     *
     * @return this
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
     * Set lang_code
     *
     * @param string $lang_code
     *
     * @return SakonninLangCode
     */
    public function setLangCode($lang_code)
    {
        $this->lang_code = $lang_code;

        return $this;
    }

    /**
     * Get lang_code
     *
     * @return string
     */
    public function getLangCode()
    {
        return $this->lang_code;
    }

    /**
     * Set template
     *
     * @param string $template
     *
     * @return SakonninTemplate
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Add message_types
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $message_types
     * @return MessageTypeType
     */
    public function addMessageType(\BisonLab\SakonninBundle\Entity\MessageType $message_types)
    {
        $this->message_types[] = $message_types;

        return $this;
    }

    /**
     * Remove message_types
     *
     * @param \BisonLab\SakonninBundle\Entity\MessageType $message_types
     */
    public function removeMessageType(\BisonLab\SakonninBundle\Entity\MessageType $message_types)
    {
        $this->message_types->removeElement($message_types);
    }

    /**
     * Get message_types
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMessageTypes()
    {
        return $this->message_types;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
