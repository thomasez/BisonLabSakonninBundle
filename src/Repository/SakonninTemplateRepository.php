<?php

namespace BisonLab\SakonninBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\SakonninTemplate;

/**
 * SakonninTemplateRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SakonninTemplateRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SakonninTemplate::class);
    }

    public function getTypesAsChoiceArray($preferred = array())
    {
    }
}
