<?php

namespace BisonLab\SakonninBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\SakonninFile;
use BisonLab\SakonninBundle\Entity\SakonninFileContext;

/**
 * SakonninFileRepository
 */
class SakonninFileRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SakonninFile::class);
    }

    public function findOneByIdOrFileId($value)
    {
        $qb = $this->createQueryBuilder('f')
              ->where('f.fileId = :file_id')
              ->setParameter("file_id", (string)$value);
        // Check if lenght is below 12? Should not relly need it.
        if (is_numeric($value))
            $qb->orWhere('f.id = :id')->setParameter("id", $value);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getOneByContext($system, $object_name, $external_id)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->getEntityManager()->createQueryBuilder();

        $qb2->select('sfc')
              ->from(SakonninFileContext::class, 'sfc')
              ->where('sfc.system = :system')
              ->andWhere('sfc.object_name = :object_name')
              ->andWhere('sfc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", (string)$external_id)
              ->setMaxResults(1);

        $file_context = $qb2->getQuery()->getResult();

        if (empty($file_context)) { return null; }

        return current($file_context)->getOwner();
    }
    
    public function findByContext($system, $object_name, $external_id)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->getEntityManager()->createQueryBuilder();

        $qb2->select('sfc')
              ->from(SakonninFileContext::class, 'sfc')
              ->where('sfc.system = :system')
              ->andWhere('sfc.object_name = :object_name')
              ->andWhere('sfc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", (string)$external_id);

        $files = new ArrayCollection();
        foreach($qb2->getQuery()->getResult() as $sfc) {
            $files->add($sfc->getOwner());
        }
        $iterator = $files->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1;
            });
        return new ArrayCollection(iterator_to_array($iterator));
    }
}
