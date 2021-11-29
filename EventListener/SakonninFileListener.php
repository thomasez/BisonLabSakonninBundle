<?php

namespace BisonLab\SakonninBundle\EventListener;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\EventSubscriber;

use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * Listen to the remove event to delete files accordingly.
 * The VichUploader handles the file itself, but this is for thumbnails.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SakonninFileListener implements EventSubscriber
{
    private $file_storage;
    private $removes = array();

    public function __construct($file_storage)
    {
        $this->file_storage = $file_storage;
    }

    /**
     * The events the listener is subscribed to.
     *
     * @return array The array of events.
     */
    public function getSubscribedEvents(): array
    {
        return array(
            'preRemove',
            'postRemove',
        );
    }

    /**
     * Ensures a proxy will be usable in the postRemove.
     *
     * @param EventArgs $event The event.
     */
    public function preRemove(EventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof SakonninFile) {
           // VichUploader removes this property when it removes the file,
           // alas I need to store it somehow.
           $this->removes[] = $entity->getStoredAs();
        }
    }

    /**
     * @param EventArgs $event The event.
     */
    public function postRemove(EventArgs $args)
    {
        foreach ($this->removes as $rfile) {
            // Gotta store the thumbs in a directory.
            $filename = $this->file_storage . "/" . $rfile;
            $thumbdir = $filename . "_thumbs";
            if (!file_exists($thumbdir) || !is_dir($thumbdir))
                continue;
            // I always love these, they are bound to go BOOOM some day.
            foreach(glob($thumbdir . "/*") as $file) {
                unlink($file);
            }
            rmdir($thumbdir);
        }
    }
}
