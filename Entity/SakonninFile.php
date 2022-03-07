<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    use \BisonLab\ContextBundle\Entity\ContextOwnerTrait;
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
     * @Vich\UploadableField(mapping="sakonnin_file", fileNameProperty="storedAs", size="size", mimeType="mimeType", originalName="name")
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
     * I am not adding notes to this, too complex and can be done by whoever
     * really needing it, in their code.
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     * This is the filename it's stored as in the file system. It's most
     * probably a combination of fileId and extension.
     *
     * @ORM\Column(name="stored_as", type="string", length=255)
     */
    private $storedAs;

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
     *
     * @ORM\Column(name="mime_type", type="string", length=100, nullable=true)
     */
    private $mimeType;

    /**
     * @var string
     * The encoding from finfo / mime type. As specific as possible.
     *
     * @ORM\Column(name="encoding", type="string", length=100, nullable=true)
     */
    private $encoding;

    /**
     * @var array
     * Tags, the simplest way (No, not simple_array).
     *
     * @ORM\Column(name="tags", type="array", nullable=true)
     */
    private $tags = [];

    /**
     * @ORM\OneToMany(targetEntity="SakonninFileContext", mappedBy="owner", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $contexts;

    public function __construct($options = array())
    {
        $this->description = $options['description'] ?? null;
        if (isset($options['file_type']))
            $this->setFileType($options['file_type']);
        $this->setFileId(uniqid());
        $this->contexts = new \Doctrine\Common\Collections\ArrayCollection();
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
        // Until php7/uploaderbundle 1.7
        if ($file instanceof UploadedFile) {
            $this->setMimeType($file->getMimeType());
            $this->setName($file->getClientOriginalName());
            $this->setSize($file->getSize());
        }
        
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
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return File
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get storedAs
     *
     * @return string
     */
    public function getStoredAs()
    {
        return $this->storedAs;
    }

    /**
     * Set storedAs
     *
     * @param string $storedAs
     *
     * @return File
     */
    public function setStoredAs($storedAs)
    {
        $this->storedAs = $storedAs;

        return $this;
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
     * Get fileType
     * @return string
     */
    public function getThumbnailable()
    {
        return self::getFileTypes()[$this->fileType]['thumbnailable'];
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     *
     * @return File
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
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
     * add a tag
     *
     * @param string $tag
     *
     * @return array
     */
    public function addTag($tag)
    {
        // Don't want a blowup.
        if (empty($tag))
            return $this->tags;
        if (array_search($tag, $this->tags))
            return $this->tags;

        $this->tags[] = $tag;

        return $this->tags;
    }

    /**
     * remove a tag
     *
     * @param string $tag
     *
     * @return array
     */
    public function removeTag($tag)
    {
        if (!$tag || empty($tag))
            return $this->tags;

        if ($idx = array_search($tag, $this->tags))
            unset($this->tags[$idx]);

        return $this->tags;
    }

    /**
     * Set tags, either comma separated or an array.
     *
     * @return array
     */
    public function setTags($tags)
    {
        if (is_array($tags)) {
            $this->tags = $tags;
        } else {
            $this->tags = array_map('trim', explode(",", $tags));
        }
        return $this->tags;
    }

    /**
     * Get the tags
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Get the stored as name with path and filename
     *
     * @return string
     */
    public function getRealPath()
    {
        // Not sure which one is the best one for now, but this seemed logical.
        // Yeah, read the docs stupid.
        return $this->file ? $this->file->getRealPath() : "";
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

    public function __toString(): string
    {
        return $this->name;
    }

    /*
     * Cheating, but close enough.
     */
    public function isImage(): bool
    {
        return strpos($this->mimeType, 'image') !== FALSE;
    }

    /*
     * Cheating, but close enough.
     */
    public function isText(): bool
    {
        return strpos($this->mimeType, 'text') !== FALSE;
    }
}
