<?php

namespace BisonLab\SakonninBundle\Service;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType as FileFormType;

use BisonLab\SakonninBundle\Entity\SakonninFile;
use BisonLab\SakonninBundle\Entity\SakonninFileContext;
use BisonLab\SakonninBundle\Controller\SakonninFileController;

/**
 * Files service.
 */
class Files
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function storeFile($data, $context_data = array())
    {
        $em = $this->getDoctrineManager();
        $file = null;
        if ($data instanceof SakonninFile) {
            $file = $data;
        } else {
            $file = new SakonninFile($data);
            if (isset($data['file_type'])) {
                $file->setFileType($file_type);
            }
        }

        // I'll add context data regardless what the data was.
        // If context data is provided we gotta handle it regardless.
        if (isset($context_data)
            && isset($context_data['system'])
            && isset($context_data['object_name'])
            && isset($context_data['external_id'])) {

            $file_context = new SakonninFileContext($context_data);
            $file->addContext($file_context);
            $em->persist($file_context);
        }
        $em->persist($file);

        // I want the eoncoding, no support for that in Symfony/SplFile yet.
        $finfo = finfo_open(FILEINFO_MIME_ENCODING);
        $encoding = finfo_file($finfo, $file->getFilenameWithPath());
        $file->setEncoding($encoding);

        if (!$file->getFileType()) {
            /*
             *  Gotta guess. I'll keep it here as with the content type above.
             *  This is the way to store/add files, so it should work out
             *  although having it in an event listener would be more correct.
             *
             * I'll start with being a coward and use the mime type. In a
             * simple manner.
             */
            $mime_type = $file->getMimeType();
            if (strpos($mime_type, 'image') !== false)
                $file->setFileType('IMAGE');
            elseif (strpos($mime_type, 'text') !== false)
                $file->setFileType('TEXT');
            else
                $file->setFileType('UNKNOWN');
        }

        /*
         * Cut&paste from Messages. Not sure I ne3ed this, and it certainly
         * does not work now.
         */
        // $dispatcher = $this->container->get('sakonnin.functions');
        // $dispatcher->dispatchFileFunctions($file);
        $em->flush();
        return $file;
    }

    public function getUploadForm($options = array())
    {
        $em = $this->getDoctrineManager();
        $file = null;
        $file_context = null;
        if (isset($options['file']) && $options['file'] instanceof File) {
             $file =  $options['file'];
        } elseif (isset($options['file_data']) && $data = $options['file_data']) {
            if (isset($data['file_type']) && $file_type = $em->getRepository('BisonLabSakonninBundle:FileType')->findOneByName($data['file_type'])) {
                $data['file_type'] = $file_type;
            }
            $file = new SakonninFile($data);
        } else {
            $file = new SakonninFile();
        }

        if (isset($options['file_context'])) {
            $file_context = new SakonninFileContext($options['file_context']);
            $file->addContext($file_context);
        }

        $c = new SakonninFileController();
        $c->setContainer($this->container);

        $form = $c->createCreateForm($file);

        // You may wonder why. It's beause this one is called from twig
        // templates as well as the file controller (Which adds stuff).
        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    public function getFilesForLoggedIn($criterias = array())
    {
        $user = $this->getLoggedInUser();
        return $this->getFilesForUser($user, $criterias);
    }

    public function getFilesForUser($user, $criterias = array())
    {
        $criterias['username'] = $user->getUsername();
        return $this->getFiles($criterias);
    }

    public function getFiles($criterias = array())
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:SakonninFile');
        $query = $repo->createQueryBuilder('f');

        if (isset($criterias['username'])) {
            $query->where('f.createdBy = :username');
            $query->setParameter('username', $criterias['username']);
        }

        if (isset($criterias['file_type'])) {
            $mt = $this->getFileType($criterias['file_type']);
            $query->andWhere("f.file_type = :file_type")
                ->setParameter('file_type', $mt);
        }

        if (isset($criterias['order'])) {
            $query->orderBy("f.createdAt", $criterias['order']);
        } else {
            $query->orderBy("f.createdAt", "ASC");
        }

        return $query->getQuery()->getResult();
    }

    public function contextHasFiles($context)
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:SakonninFileContext');
        return $repo->contextHasFiles($context);
    }
}
