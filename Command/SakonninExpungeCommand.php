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
class SakonninExpungeCommand extends ContainerAwareCommand
{

    private $verbose = true;
    private $mt_cache = array();

    protected function configure()
    {
        $this->setDefinition(array(
                new InputOption('doit', '', InputOption::VALUE_REQUIRED, 'And you have to set it with --doit=yes to make it happen')
                ))
                ->setDescription('Use the expunge days value in Message Type to delete older messages..')
                ->setHelp(<<<EOT
This is for cleaning up old messages based on the Expunge Days value on the Message Types.

Option --doit=yes to enable real deletion. (Not just a message that it's supposed to delete.)
EOT
            );

        $this->setName('sakonnin:expunge');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->doit      = $input->getOption('doit');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        // This is to make sure we don't end up with massige memory useage. 
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->mt_repo    = $this->entityManager
                ->getRepository('BisonLabSakonninBundle:MessageType');
        $this->m_repo    = $this->entityManager
                ->getRepository('BisonLabSakonninBundle:Message');

        // First, Find the message types that has expunge.
        // I'll just pick the types, not the groups (parents)
        $mt_iterable = $this->mt_repo->createQueryBuilder('mt')
            ->where('mt.parent is not null')
            ->andWhere('mt.expunge_days > 0')
            ->getQuery()->iterate();

        while (($res = $mt_iterable->next()) !== false) {
            $mt = $res[0];
            $edays = $mt->getExpungeDays();
            $output->writeln("Will Expunge messages from " . $mt->getName() . " with age over " . $edays . " days.");

            // First, Find the message types that has expunge.
            // I'll just pick the types, not the groups (parents)
            // $m_iterable = $this->m_repo->createQueryBuilder('m')
            $m_query = $this->m_repo->createQueryBuilder('m')
                ->where('m.message_type = :mt')
                ->andWhere('date_diff(CURRENT_TIMESTAMP(), m.createdAt ) > :edays')
                ->setParameter('mt', $mt)
                ->setParameter('edays', $edays)
                ->getQuery();

            $m_iterable = $m_query->iterate();

            while (($mess = $m_iterable->next()) !== false) {
                $message = $mess[0];
                
                $output->writeln("Will Expunge " . $message->getSubject());
                if ($this->doit == "yes") {
                    $this->entityManager->remove($message);
                }
            }

            if ($this->doit == "yes") {
                $this->entityManager->flush();
            }
        }
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

