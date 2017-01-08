<?php

namespace BisonLab\SakonninBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * MessageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MessageRepository extends EntityRepository
{
    public function getOneByContext($system, $object_name, $external_id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->_em->createQueryBuilder();

        $qb2->select('mc')
              ->from('BisonLab\SakonninBundle\Entity\MessageContext', 'mc')
              ->where('mc.system = :system')
              ->andWhere('mc.object_name = :object_name')
              ->andWhere('mc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id)
              ->setMaxResults(1);

        $message_context = $qb2->getQuery()->getResult();

        if (empty($message_context)) { return null; }

        return current($message_context)->getMessage();
    }
    
    public function findByContext($system, $object_name, $external_id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb2 = $this->_em->createQueryBuilder();

        $qb2->select('mc')
              ->from('BisonLab\SakonninBundle\Entity\MessageContext', 'mc')
              ->where('mc.system = :system')
              ->andWhere('mc.object_name = :object_name')
              ->andWhere('mc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id);

        $messages = new ArrayCollection();
        foreach($qb2->getQuery()->getResult() as $mc) {
            $messages->add($mc->getMessage());
        }
        $iterator = $messages->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1;
            });
        return new ArrayCollection(iterator_to_array($iterator));
    }
}
