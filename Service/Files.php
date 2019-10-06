<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType as FileFormType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
                $file->setFileType($data['file_type']);
            }
            if (isset($data['description'])) {
                $file->setDescription($data['description']);
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
        $encoding = finfo_file($finfo, $file->getRealPath());
        $file->setEncoding($encoding);

        if (!$file->getFileType() || $file->getFileType() == "AUTO") {
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
         * Cut&paste from Messages. Does not do all that one can, for now.
         * 
         */
        $dispatcher = $this->container->get('sakonnin.functions');
        $dispatcher->dispatchFileFunctions($file);
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

    public function getDeleteForm($file, $options = array())
    {
        $c = new SakonninFileController();
        $c->setContainer($this->container);
        $form = $c->createDeleteForm($file);

        // You may wonder why. It's beause this one is called from twig
        // templates as well as the file controller (Which adds stuff).
        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    public function getFilesForContext($criterias)
    {
        $criterias['context'] = $criterias;
        return $this->getFiles($criterias);
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

        // There can be only one
        if (isset($criterias['fileid'])) {
            return $repo->findOneBy(['fileId' => $criterias['fileid']]);
        }
        if (isset($criterias['id'])) {
            return $repo->findOneBy(['id' => $criterias['id']]);
        }

        $query = $repo->createQueryBuilder('f');

        if (isset($criterias['context'])) {
            $query->innerJoin('f.contexts', 'fc')
                ->where('fc.system = :system')
                ->andWhere('fc.object_name = :object_name')
                ->andWhere('fc.external_id = :external_id')
                ->setParameter('system', $criterias['context']['system'])
                ->setParameter('object_name', $criterias['context']['object_name'])
                ->setParameter('external_id', $criterias['context']['external_id'])
            ;
        }

        if (isset($criterias['username'])) {
            $query->andWhere('f.createdBy = :username');
            $query->setParameter('username', $criterias['username']);
        }

        if (isset($criterias['file_type'])) {
            $query->andWhere("f.fileType = :fileType")
                ->setParameter('fileType', $criterias['file_type']);
        }

        if (isset($criterias['description'])) {
            $query->andWhere("f.description = :description")
                ->setParameter('description', $criterias['description']);
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

    public function getStoredFileName(SakonninFile $sfile)
    {
        // TODO: Add access control.
        $path = $this->container->getParameter('sakonnin.file_storage');
        $filename = $path . "/" . $sfile->getStoredAs();
        return $filename;
    }

    public function getStoredFile(SakonninFile $sfile)
    {
        // TODO: Add access control.
        $path = $this->container->getParameter('sakonnin.file_storage');
        $filename = $path . "/" . $sfile->getStoredAs();
        // Not entirely sure this is a good idea. Supposed to be binary safe.
        return file_get_contents($filename);
    }

    public function getMaxFilesize()
    {
        return UploadedFile::getMaxFilesize();
    }

    public function getThumbnailFilename(SakonninFile $sfile, $x, $y)
    {
        if (!$sfile->getThumbnailable() || !is_numeric($x) || !is_numeric($y)) {
            return null;
        }
        $path = $this->container->getParameter('sakonnin.file_storage');
        // Gotta store the thumbs in a directory.
        $filename = $path . "/" . $sfile->getStoredAs();
        $thumbdir = $filename . "_thumbs";
        $thumbname = $thumbdir . "/" . $x . "_" . $y . "_" . $sfile->getStoredAs();
        if (file_exists($thumbname))
            return $thumbname;
        if (!file_exists($thumbdir))
            mkdir ($thumbdir);
        $imagine = new \Imagine\Gd\Imagine();
        $image = $imagine->open($filename);
        $thumb = $image->thumbnail(new \Imagine\Image\Box($x, $y));
        $thumb->save($thumbname);

        return $thumbname;
    }
}
