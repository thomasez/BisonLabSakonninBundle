<?php

namespace BisonLab\SakonninBundle\Entity;

use BisonLab\CommonBundle\Entity\ContextBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * BisonLab\SakonninBundle\Entity\FileContext
 *
 * @ORM\Table(name="sakonnin_filecontext")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\FileContextRepository")
 */
class FileContext
{
    use \BisonLab\CommonBundle\Entity\ContextBaseTrait;

    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="File", inversedBy="contexts")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=false)
     */
    private $file;

    public function __construct($options = array())
    {
        if (isset($options['file'])) 
            $this->setFile($options['file']);
        return $this->traitConstruct($options);
    }

    /** 
     * Set file
     *
     * @param object $file
     */
    public function setFile(File $file)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return object 
     */
    public function getFile()
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
        return $this->setFile($object);
    }

    /**
     * Generic main object.
     *
     * @return object 
     */
    public function getOwner()
    {
        return $this->getFile();
    }

    public function getOwnerEntityAlias()
    {
        return "BisonLabSakonninBundle:File";
    }

    public function isDeleteable()
    {
        return true;
    }
}
