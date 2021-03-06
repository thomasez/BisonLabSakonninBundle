<?php

namespace BisonLab\SakonninBundle\Entity;

use BisonLab\CommonBundle\Entity\ContextBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * BisonLab\SakonninBundle\Entity\SakonninFileContext
 *
 * @ORM\Table(name="sakonnin_filecontext")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\SakonninFileContextRepository")
 */
class SakonninFileContext
{
    use \BisonLab\CommonBundle\Entity\ContextBaseTrait;

    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="SakonninFile", inversedBy="contexts")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=false)
     */
    private $file;

    public function __construct($options = array())
    {
        if (isset($options['file'])) 
            $this->setSakonninFile($options['file']);
        return $this->traitConstruct($options);
    }

    /** 
     * Set file
     *
     * @param object $file
     */
    public function setSakonninFile(SakonninFile $file)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return object 
     */
    public function getSakonninFile()
    {
        return $this->file;
    }

    /**
     * Generic main object setting.
     *
     * @return object 
     */
    public function setOwner($object)
    {
        return $this->setSakonninFile($object);
    }

    /**
     * Generic main object.
     *
     * @return object 
     */
    public function getOwner()
    {
        return $this->getSakonninFile();
    }

    public function getOwnerEntityAlias()
    {
        return "BisonLabSakonninBundle:SakonninFile";
    }

    public function isDeleteable()
    {
        return true;
    }
}
