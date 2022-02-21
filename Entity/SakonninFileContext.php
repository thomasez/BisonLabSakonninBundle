<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BisonLab\SakonninBundle\Entity\SakonninFileContext
 *
 * @ORM\Table(name="sakonnin_filecontext")
 * @ORM\Entity(repositoryClass="BisonLab\SakonninBundle\Repository\SakonninFileContextRepository")
 */
class SakonninFileContext
{
    use \BisonLab\ContextBundle\Entity\ContextBaseTrait;

    /**
     * @var mixed
     *
     * @ORM\ManyToOne(targetEntity="SakonninFile", inversedBy="contexts", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     */
    private $owner;

    public function getOwnerEntityAlias()
    {
        return "BisonLabSakonninBundle:SakonninFile";
    }

    public function isDeleteable()
    {
        return true;
    }
}
