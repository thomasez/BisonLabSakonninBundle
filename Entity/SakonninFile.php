<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;
use BisonLab\SakonninBundle\Entity\MessageContext as Context;

/**
 * SakonninFile
 *
 * @ORM\Table(name="sakonnin_file")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\SakonninFileRepository")
 * @Vich\Uploadable
 */
class SakonninFile
{
    use \BisonLab\CommonBundle\Entity\ContextOwnerTrait;
    use TimestampableEntity;
    use BlameableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple
     * property.
     * 
     * @Vich\UploadableField(mapping="sakonnin_file", fileNameProperty="name", size="size")
     * 
     * @var File
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * A uniquue ID, as also use byt message.
     *
     * @ORM\Column(name="file_id", type="string", length=100, unique=true)
     */
    private $fileId;

    /**
     * @var integer $size
     *
     * Simplest form, just a raw hint at what the files is or contains.
     *
     * @ORM\Column(name="size", type="integer", nullable=true)
     */
    private $size;

    /**
     * @var string $file_type
     *
     * Simplest form, just a raw hint at what the files is or contains.
     *
     * @ORM\Column(name="file_type", type="string", length=40, nullable=false)
     */
    private $fileType;

    /**
     * @var string
     * The type from finfo / mime type. As specific as possible.
     *
     * @ORM\Column(name="content_type", type="string", length=100, nullable=true)
     */
    private $contentType;

    /**
     * @var string
     * The encoding from finfo / mime type. As specific as possible.
     *
     * @ORM\Column(name="encoding", type="string", length=100, nullable=true)
     */
    private $encoding;

    /**
     * @ORM\OneToMany(targetEntity="SakonninFileContext", mappedBy="file", cascade={"persist", "remove"})
     */
    private $contexts;

    public function __construct($options = array())
    {
        $this->setFileId(uniqid());
        return $this->traitConstruct($options);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /*
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Product
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;
        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return File
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
     * Set fileId
     *
     * @param string $fileId
     *
     * @return File
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return string
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set size
     *
     * @param string $size
     *
     * @return File
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set fileType
     *
     * @param string $
     * @return Message
     */
    public function setFileType($fileType)
    {
        if ($fileType == $this->fileType) return $this;
        $fileType = strtoupper($fileType);
        if (!isset(self::getFileTypes()[$fileType])) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid file type.', $fileType));
        }

        $this->fileType = $fileType;
        return $this;
    }

    /**
     * Get fileType
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set contentType
     *
     * @param string $contentType
     *
     * @return File
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
     * Set encoding
     *
     * @param string $encoding
     *
     * @return File
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Get Address Types (For use by FromType and ToType)
     *
     * @return array
     */
    public static function getFileTypes()
    {
        return ExternalEntityConfig::getFileTypes();
    }
}
