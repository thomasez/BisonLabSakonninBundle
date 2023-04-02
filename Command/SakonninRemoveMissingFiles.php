<?php

namespace BisonLab\SakonninBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use BisonLab\SakonninBundle\Entity\SakonninFile;

/**
 * Imports message types. Either for fixtures or just install for prod.
 *
 * @author Thomas Lundquist <thomasez@bisonlab.no>
 */
class SakonninRemoveMissingFiles extends Command
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    protected static $defaultName = 'sakonnin:remove-missing-files';

    private $verbose = true;
    private $mt_cache = array();

    protected function configure()
    {
        $this
            ->setDescription('Use the expunge days value in Message Type to delete older messages..')
           ->addOption('doit', '', InputOption::VALUE_REQUIRED, 'And you have to set it with --doit=yes to make it happen')
           ->setHelp(<<<EOT
This is to check if the files in the database is actually available for viewing and downloading.

Option --doit=yes to enable deletion of the entries in the DB if there is not file.
EOT
            );
    }

    public function __construct($container)
    {
        $this->container = $container;
        $this->entityManager = $this->getDoctrineManager();
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->doit = $input->getOption('doit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // This is to make sure we don't end up with massige memory useage. 
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->file_repo = $this->entityManager
                ->getRepository(SakonninFile::class);

        // First, Find the message types that has expunge.
        // I'll just pick the types, not the groups (parents)
        // TODO: Probably also handle parent/group.
        $iterable = $this->file_repo->createQueryBuilder('f')
            ->getQuery()->iterate();
        $filepath = $this->container->getParameter('sakonnin.file_storage');

        while (($res = $iterable->next()) !== false) {
            $file = $res[0];
            // Should not really happen.
            if (!$filename = $file->getStoredAs()) {
                continue;
            }
            $path = $filepath. "/" . $file->getStoredAs();
            if (file_exists($path)) {
                continue;
            }

            if ($this->doit == "yes") {
                $this->entityManager->remove($file);
                $this->entityManager->flush();
            }
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
