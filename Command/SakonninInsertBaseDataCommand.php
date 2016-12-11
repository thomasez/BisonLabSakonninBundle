<?php

namespace BisonLab\SakonninBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BisonLab\SakonninBundle\Entity\MessageType as MessageType;

/**
 * Imports message types. Either for fixtures or just install for prod.
 *
 * @author Thomas Lundquist <thomasez@bisonlab.no>
 */
class SakonninInsertBaseDataCommand extends ContainerAwareCommand
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $verbose = true;
    private $mt_cache = array();

    private $message_types = array(
       'Email' => array('description' => 'Emails'),
           'Manual' => array('parent' => 'Email', 'description' => "Emails sent by people"),
           'Automated' => array('parent' => 'Email', 'description' => "Emails sent by a system"),
       'Messages' => array('description' => 'Messaging'),
           'PM' => array('parent' => 'Messages', 'description' => "Personal Message"),
           'Wall' => array('parent' => 'Messages', 'description' => "Messages for the front page"),
           'Broadcast' => array('parent' => 'Messages', 'description' => "Send PM to everyone"),
    );

    protected function configure()
    {
        $this->setDescription('Inserts the data we need for a working Sakonnin.')
                ->setHelp(<<<EOT
Inserts the data we need for a working Sakonnin.

You could call this "Load fixtures" as well. It's for preparing the system for use.
EOT
            );

        $this->setName('sakonnin:insert:basedata');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getDoctrineManager();

        $this->mt_repo    = $this->entityManager
                ->getRepository('BisonLabSakonninBundle:MessageType');

        foreach ($this->message_types as $name => $type) {

            // Handling a parent.
            $parent = null;
            if (isset($type['parent']) && !$parent = $this->_findMt($type['parent'])) {
                error_log("Could not find the group " . $type['parent']);
                return false;
            }

            $mt = new MessageType();

            $mt->setName($name);
            if (isset($type['description']))
                $mt->setDescription($type['description']);
            if (isset($type['callback_function']))
                $mt->setCallbackFunction($type['callback_function']);
            if (isset($type['callback_type']))
                $mt->setCallbackType($type['callback_type']);
            if (isset($type['forward_function']))
                $mt->setForwardFunction($type['forward_function']);
            if (isset($type['forward_function']))
                $mt->setForwardType($type['forward_typ']);
            $this->entityManager->persist($mt);
            if ($parent) {
                $output->writeln("Setting parent " . $parent->getName() . " on " . $mt->getName());
                $parent->addChild($mt);
                $this->entityManager->persist($parent);
            }

            if ($mt)
                $this->mt_cache[$mt->getName()] = $mt;
            $output->writeln("Created " . $mt->getName());

        }
        $this->entityManager->flush();
    }

    private function _findMt($name) {
        if (isset($this->mt_cache[$name]))
            return $this->mt_cache[$name];
            
        $mt = $this->mt_repo->findOneByName($name);
        if ($mt)
            $this->mt_cache[$name] = $mt;
        else
            return null;

        return $this->mt_cache[$name];
    }
}
