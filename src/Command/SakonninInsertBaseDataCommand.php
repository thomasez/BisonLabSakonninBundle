<?php

namespace BisonLab\SakonninBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\MessageType as MessageType;

/**
 * Inserts a fixed set of message types and can also be used for
 * other data to be inserted at database creation.
 *
 * @author Thomas Lundquist <thomasez@bisonlab.no>
 */
class SakonninInsertBaseDataCommand extends Command
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    protected static $defaultName = 'sakonnin:insert-basedata';

    private $verbose = true;
    private $managerRegistry;
    private $entityManager;
    private $mt_cache = array();

    private $message_types = array(
       'Emails' => array(
                'description' => 'Emails'
                ),
           'Manual' => array(
                'parent' => 'Emails',
                'security_model' => 'PRIVATE',
                'base_type' => 'MESSAGE',
                'description' => "Emails sent by people to a user"
                ),
           'Automated' => array(
                'parent' => 'Emails',
                'base_type' => 'MESSAGE',
                'security_model' => 'PRIVATE',
                'description' => "Emails sent by a system to a user"
                ),
       'Messages' => array(
                'description' => 'Messaging'
                ),
           'PM' => array(
                'parent' => 'Messages',
                'base_type' => 'MESSAGE',
                'security_model' => 'PRIVATE',
                'description' => "Personal Message"
                ),
           'Notification' => array(
                'parent' => 'Messages',
                'base_type' => 'MESSAGE',
                'security_model' => 'PRIVATE',
                'expunge_days' => 5,
                'description' => "Notification"
                ),
       'Announcements' => array(
                'description' => 'Announcements'
                ),
           'Wall' => array(
                'parent' => 'Announcements',
                'base_type' => 'NOTE',
                'security_model' => 'ALL_READ',
                'description' => "The Wall"
                ),
       'Notes' => array(
                'description' => 'Notes'
                ),
           'Note' => array(
                'parent' => 'Notes',
                'base_type' => 'NOTE',
                'security_model' => 'ALL_READ',
                'description' => "Note everyone can read"
                ),
    );

    protected function configure()
    {
        $this
            ->setDescription('Inserts or updates the data we need for a working Sakonnin.')
            ->setHelp(<<<EOT
Inserts or updates the data we need for a working Sakonnin.

You could call this "Load fixtures" as well. It's for preparing the system for use.
EOT
            );
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $this->getDoctrineManager();
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->mt_repo = $this->entityManager
                ->getRepository(MessageType::class);

        foreach ($this->message_types as $name => $type) {
            // Handling a parent.
            $parent = null;
            if (isset($type['parent']) && !$parent = $this->_findMt($type['parent'])) {
                error_log("Could not find the group " . $type['parent']);
                return 1;
            }

            // Now we can update.
            if (!$mt = $this->_findMt($name))
                $mt = new MessageType();

            $mt->setName($name);
            if (isset($type['base_type']))
                $mt->setBaseType($type['base_type']);
            if (isset($type['description']))
                $mt->setDescription($type['description']);
            if (isset($type['callback_function']))
                $mt->setCallbackFunction($type['callback_function']);
            $mt->setSecurityModel($type['security_model'] ?? "PRIVATE");
            if (isset($type['forward_function']))
                $mt->setForwardFunction($type['forward_function']);
            if (isset($type['expunge_days']))
                $mt->setExpungeDays($type['expunge_days']);

            $mt->setExpungeMethod($type['expunge_method'] ?? "DELETE");
            $mt->setExpireMethod($type['expire_method'] ?? "DELETE");
            $this->entityManager->persist($mt);
            if ($parent) {
                $output->writeln("Setting parent " 
                    . $parent->getName() . " on " . $mt->getName());
                $parent->addChild($mt);
                $this->entityManager->persist($parent);
            }
            if ($mt)
                $this->mt_cache[$mt->getName()] = $mt;
            $output->writeln("Created " . $mt->getName());
        }
        $this->entityManager->flush();
        return 0;
    }

    private function _findMt($name)
    {
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
