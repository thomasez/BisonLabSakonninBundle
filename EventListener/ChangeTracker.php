<?php

namespace BisonLab\SakonninBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

use BisonLab\SakonninBundle\Entity\Message;

/*
 * Some of this is so wrong I am afraid I'll go mad.
 */

class ChangeTracker 
{

    private $container;
    private $work_list = array();

    public function setContainer($container)
    {
         $this->container = $container;
    }

    // Do I need this one?
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {

    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Message) {
                // Here I have to dispatch all the forward functions and so
                // on. Or rather, call them.
                // Not sure I need it as a service or just a few static
                // functions in a Library. 
                // $messagedispatcher = $this->container->get('refresher');

                // Or, put all of it on a work list for the postFlush to
                // handle. It's not done before it's saved..
                // $this->work_list[] = $entity;

                // And, should I search for references and connect the replies
                // here? If a "in reply to" - search and connect? Too late I
                // think.
                // prePersist perhaps?
            
            }
        }

    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
    }

}
