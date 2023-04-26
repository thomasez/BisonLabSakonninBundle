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
class SakonninRemoveMissingFilesCommand extends Command
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    protected static $defaultName = 'sakonnin:remove-missing-files';

    private $verbose = true;

    protected function configure()
    {
        $this
            ->setDescription('Removes file objects where there is no file connected to it.')
           ->addOption('doit', '', InputOption::VALUE_REQUIRED, 'And you have to set it with --doit=yes to make it happen')
           ->setHelp(<<<EOT
Some times you end up with a file object and no file in the file storage. This just removes the objects so that you will not end up with a 404 and a listing of files not existing.

Option --doit=yes to enable real deletion. (Not just a message that it's supposed to delete.)
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

        $sf_repo = $this->entityManager
                ->getRepository(SakonninFile::class);

        $sf_iterable = $sf_repo->createQueryBuilder('sf')
            ->getQuery()->iterate();

        $sakonnin_files = $this->container->get('sakonnin.files');

        while (($res = $sf_iterable->next()) !== false) {
            $sfile = $res[0];

            if (!file_exists($sakonnin_files->getStoredFileName($sfile))) {
                $output->writeln("Will Expunge " . $sfile->getName());
                if ($this->doit == "yes") {
                    $this->entityManager->remove($sfile);
                    $this->entityManager->flush();
                }
            }
        }
        return 0;
    }
}
