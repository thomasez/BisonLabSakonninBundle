<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType as FileFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Vich\UploaderBundle\Form\Type\VichFileType;

use BisonLab\SakonninBundle\Entity\SakonninFile;
use BisonLab\SakonninBundle\Entity\SakonninFileContext;
use BisonLab\SakonninBundle\Service\Functions as SakonninFunctions;

/**
 * Files service.
 */
class Files
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $router;
    private $formBuilder;
    private $parameterBag;
    private $tokenStorage;
    private $entityManager;
    private $managerRegistry;
    private $sakonninFunctions;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, ManagerRegistry $managerRegistry, ParameterBagInterface $parameterBag, SakonninFunctions $sakonninFunctions, FormFactoryInterface $formBuilder, RouterInterface $router)
    {
        $this->router = $router;
        $this->formBuilder = $formBuilder;
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->managerRegistry = $managerRegistry;
        $this->sakonninFunctions = $sakonninFunctions;
    }

    public function storeFile($data, $context_data = array())
    {
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

        // I want the eoncoding, no support for that in Symfony/SplFile yet.
        $finfo = finfo_open(FILEINFO_MIME_ENCODING);
        $encoding = finfo_file($finfo, $file->getRealPath());
        $file->setEncoding($encoding);

        if (!$file->getFileType() || $file->getFileType() == "AUTO") {
            /*
             * Gotta guess. I'll keep it here as with the content type above.
             * This is the way to store/add files, so it should work out
             * although having it in an event listener would be more correct.
             */
            if ($file->isImage())
                $file->setFileType('IMAGE');
            elseif ($file->isText())
                $file->setFileType('TEXT');
            else
                $file->setFileType('UNKNOWN');
        }

        // I'll add context data regardless what the data was.
        // If context data is provided we gotta handle it regardless.
        // I wonder what doctrine has done to make persist not be enough to get
        // an id any more.
        $this->entityManager->persist($file);
        $this->entityManager->flush();
        // $this->entityManager->persist($file);
        if (isset($context_data)
            && isset($context_data['system'])
            && isset($context_data['object_name'])
            && isset($context_data['external_id'])) {

            $file_context = new SakonninFileContext($context_data);
            $file->addContext($file_context);
            $this->entityManager->persist($file_context);
        }

        /*
         * Cut&paste from Messages. Does not do all that one can, for now.
         */
        $this->sakonninFunctions->dispatchFileFunctions($file);
        $this->entityManager->flush();
        return $file;
    }

    public function getUploadForm($options = array())
    {
        $file = null;
        $file_context = null;
        if (isset($options['file']) && $options['file'] instanceof File) {
             $file =  $options['file'];
        } elseif (isset($options['file_data']) && $data = $options['file_data']) {
            $file = new SakonninFile($data);
        } else {
            $file = new SakonninFile();
        }

/*
 * Not sure this is the right thing to do.
 * TODO: Decide on doing this here on storeFile where it is now.
        if (isset($options['file_context'])) {
            $file_context = new SakonninFileContext($options['file_context']);
            $file->addContext($file_context);
        }
 */

        if ($ft = $options['file_type'] ?? null) {
            $file->setFileType($ft);
        }

        if ($tags = $options['tags'] ?? null) {
            $file->setTags($tags);
        }

        $form = $this->createCreateForm($file);

        if (isset($options['no_description'])) {
            $form->remove('description');
        }

        if (isset($options['no_tags'])) {
            $form->remove('tags');
        }
        if (!isset($options['no_submit'])) {
            $form->add('submit', SubmitType::class, array('label' => 'Save'));
        }

        // You may wonder why. It's beause this one is called from twig
        // templates as well as the file controller (Which adds stuff).
        if (isset($options['create_view'])) 
            return $form->createView();
        else
            return $form;
    }

    public function getDeleteForm($file, $options = array())
    {
        $form = $this->createDeleteForm($file);

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
        $repo = $this->entityManager->getRepository('BisonLabSakonninBundle:SakonninFile');

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

        if (isset($criterias['limit'])) {
            $query->setMaxResults($criterias['limit']);
        }
        return $query->getQuery()->getResult();
    }

    public function contextHasFiles($context)
    {
        $repo = $this->entityManager->getRepository('BisonLabSakonninBundle:SakonninFileContext');
        return $repo->contextHasFiles($context);
    }

    public function getStoredFileName(SakonninFile $sfile)
    {
        // TODO: Add access control.
        $path = $this->parameterBag->get('sakonnin.file_storage');
        $filename = $path . "/" . $sfile->getStoredAs();
        return $filename;
    }

    public function getStoredFile(SakonninFile $sfile)
    {
        // TODO: Add access control.
        $path = $this->parameterBag->get('sakonnin.file_storage');
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
        $path = $this->parameterBag->get('sakonnin.file_storage');
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

    public function createCreateForm(SakonninFile $sfile)
    {
        $route = $this->router->generate('sakonninfile_new');
        $form = $this->formBuilder->create(\BisonLab\SakonninBundle\Form\SakonninFileType::class, $sfile, array(
            'action' => $route,
            'method' => 'POST',
        ));
        $form->add('file', VichFileType::class, [
            'required' => true,
            'allow_delete' => true,
        ]);
        return $form;
    }

    public function createDeleteForm(SakonninFile $sfile)
    {
        $route = $this->router->generate('sakonninfile_delete', array(
                'file_id' => $sfile->getFileId()));
        return $this->formBuilder->createBuilder()
            ->setAction($route)
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
