<?php

namespace BisonLab\SakonninBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;

use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Entity\Message;

/**
 * Imports message types. Either for fixtures or just install for prod.
 *
 * @author Thomas Lundquist <thomasez@bisonlab.no>
 */

#[AsCommand(
    name: 'sakonnin:expunge',
    description: 'Use the expunge days value in Message Type to delete older messages..'
)]
class SakonninExpungeCommand extends Command
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $verbose = true;
    private $managerRegistry;
    private $entityManager;
    private $mt_cache = array();

    protected function configure(): void
    {
        $this
           ->addOption('doit', '', InputOption::VALUE_REQUIRED, 'And you have to set it with --doit=yes to make it happen')
           ->setHelp(<<<EOT
This is for cleaning up old messages based on the Expunge Days value on the Message Types and "Expire at" on messages if it's set.

Option --doit=yes to enable real deletion. (Not just a message that it's supposed to delete.)
EOT
            );
    }

    /*
     * Not entirely following the flow with setting entityManager here instead
     * of using getDoctrineManager all over. But it works.
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $this->getDoctrineManager();
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->doit = $input->getOption('doit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // This is to make sure we don't end up with massige memory useage. 
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->mt_repo = $this->entityManager
                ->getRepository(MessageType::class);
        $this->m_repo  = $this->entityManager
                ->getRepository(Message::class);

        // First, Find the message types that has expunge.
        // I'll just pick the types, not the groups (parents)
        // TODO: Probably also handle parent/group.
        $mt_iterable = $this->mt_repo->createQueryBuilder('mt')
            ->where('mt.parent is not null')
            ->andWhere('mt.expunge_days > 0')
            ->getQuery()->iterate();

        while (($res = $mt_iterable->next()) !== false) {
            $mt = $res[0];
            $expunge_method = $mt->getExpungeMethod();

            $edays = $mt->getExpungeDays();
            $output->writeln("Will Expunge messages from " . $mt->getName() . " with age over " . $edays . " days.");

            // First, Find the message types that has expunge.
            // I'll just pick the types, not the groups (parents)
            // $m_iterable = $this->m_repo->createQueryBuilder('m')
            $m_query = $this->m_repo->createQueryBuilder('m')
                ->where('m.message_type = :mt')
                ->andWhere('date_diff(CURRENT_TIMESTAMP(), m.createdAt ) > :edays')
                ->andWhere('m.state not in (:states)')
                ->setParameter('mt', $mt)
                ->setParameter('edays', $edays)
                ->setParameter('states', ['ARCHIVED'])
                ->getQuery();

            $m_iterable = $m_query->iterate();

            while (($mess = $m_iterable->next()) !== false) {
                $message = $mess[0];
                // TODO: Explain to myself why this.. newest from newest..
                $newest = $message->getNewestInThread();
                $i = $newest->getNewestInThread()->getCreatedAt()->diff(new \DateTime());
                // drop if newer than the expunge.
                if ($edays >= (int)$i->format('%a') ) continue;
                // I am kinda hoping cascade remove and orphanremoval will do
                // the delete whole thread deed.
                $output->writeln("Will Expunge " . $message->getSubject());
                if ($this->doit == "yes" && $expunge_method == "DELETE") {
                    $this->entityManager->remove($message);
                } elseif ($expunge_method == "ARCHIVE") {
                    $message->setState("ARCHIVED");
                }
            }

            if ($this->doit == "yes") {
                $this->entityManager->flush();
            }
        }

        // And scondly, handle expire at.
        $m_query = $this->m_repo->createQueryBuilder('m')
            ->where('m.expire_at is not null')
            ->andWhere('CURRENT_TIMESTAMP() > m.expire_at')
            ->andWhere('m.state not in (:states)')
            ->setParameter('states', ['ARCHIVED'])
            ->getQuery();

        $m_iterable = $m_query->iterate();

        while (($mess = $m_iterable->next()) !== false) {
            $message = $mess[0];
            $expire_method = $message->getMessageType()->getExpireMethod();

            /*
             * When expunging I am checking newest in thread. But this is
             * another matter. This is a set time for an expire, and since
             * someone has decided that, I'll listen and kill it.
             */
            
            // I am kinda hoping cascade remove and orphanremoval will do
            // the delete whole thread deed.
            $output->writeln("Will Expunge from set date " . $message->getSubject());
            if ($this->doit == "yes" && $expire_method == "DELETE") {
                $this->entityManager->remove($message);
            } elseif ($expire_method == "ARCHIVE") {
                $message->setState("ARCHIVED");
            }
        }
        if ($this->doit == "yes") {
            $this->entityManager->flush();
        }
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
