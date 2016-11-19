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
class SakonninImportMessageTypesCommand extends ContainerAwareCommand
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $verbose = true;
    private $mt_cache = array();

    protected function configure()
    {
        $this->setDefinition(array(
                new InputOption('delimiter', '', InputOption::VALUE_REQUIRED, 'Field delimiter, defaults to ; semicolon'),
                new InputOption('file', '', InputOption::VALUE_REQUIRED, 'Need a file')
                ))
                ->setDescription('Import message types.')
                ->setHelp(<<<EOT
This command reads a CVS and import the lines as message types.

Format:

 name;group_name(if in a group);description;callback_function;callback_type;forward_function;forward_type

Options: --file=<filename> --delimiter=',' 

Default delimiter is comma (,)
EOT
            );

        $this->setName('sakonnin:messagetype:load');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->filename      = $input->getOption('file');
        $this->delimiter = $input->getOption('delimiter') ? $input->getOption('delimiter') : ',';

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!$this->filename)
        {
           $output->writeln("I do need a filename");
           exit;
        }

        gc_enable();

        if (!($handle = fopen($this->filename, 'r')))
        {
           $output->writeln("Could not open file (".$this->filename.")");
           exit;
        }

        // Hack, get rid of BOM if it's there.
        $bom = fread($handle, 3); 
         if ($bom != b"\xEF\xBB\xBF") 
         rewind($handle);

        $this->entityManager = $this->getDoctrineManager();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->mt_repo    = $this->entityManager
                ->getRepository('BisonLabSakonninBundle:MessageType');

        while (($data = fgetcsv($handle, 1000, $this->delimiter)) !== FALSE) {

            // Blank line or equivalent.
            if (count($data) < 2) {
              continue;
            }

            // Handling a parent.
            $parent = null;
            if (!empty($data[1]) && !$parent = $this->_findMt($data[1])) {
                error_log("Could not find the group " . $data[1]);
                return false;
            }

            $mt = null;
            if (!$mt = $this->_findMt($data[0])) {
                $mt = new MessageType();
            }

            $mt->setName($data[0]);
            $mt->setDescription($data[2]);
            $mt->setCallbackFunction($data[3]);
            $mt->setCallbackType($data[4]);
            $mt->setForwardFunction($data[5]);
            $mt->setForwardType($data[6]);
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

