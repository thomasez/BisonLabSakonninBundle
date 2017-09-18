<?php

namespace BisonLab\SakonninBundle\Service;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use BisonLab\SakonninBundle\Entity\File;
use BisonLab\SakonninBundle\Entity\FileContext;
use BisonLab\SakonninBundle\Controller\FileController;

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
        if ($data instanceof File) {
            $file = $data;
        } else {
            $file = new File($data);
            if (isset($data['file_type'])) {
                $file->setFileType($file_type);
            } else {
                $file->setFileType("UNKNOWN");
            }

            if (isset($context_data)
                && isset($context_data['system'])
                && isset($context_data['object_name'])
                && isset($context_data['external_id'])) {

                $file_context = new FileContext($context_data);
                $file->addContext($file_context);
                $em->persist($file_context);
            }
        }

        // Gotta set the conteent type.
        $content_type = mime_content_type( $filename);
        $file->setContentType($content_type);
        $finfo = finfo_open(FILEINFO_MIME_ENCODING);
        $encoding = finfo_file($finfo, $filename);
        $file->setEncoding($encoding);

        $em->persist($file);

        // I planned to use an event listener to dispatch callback/forward
        // functions, but why? This storeFile functions shall be the only
        // entry point for creating files, so why should I bother?
        $dispatcher = $this->container->get('sakonnin.functions');
        $dispatcher->dispatchFileFunctions($file);

        $em->flush();
        return $file;
    }

    public function getCreateForm($options = array())
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
            $file = new File($data);
        } else {
            $file = new File();
        }

        if (isset($options['file_context'])) {
            $file_context = new FileContext($options['file_context']);
            $file->addContext($file_context);
        }

        // What does the form say?
        if (isset($options['file_data']['in_reply_to'])) {
            if (!$reply_to = $em->getRepository('BisonLabSakonninBundle:File')->findOneBy(array('file_id' => $options['file_data']['in_reply_to']))) {
                return false;
            } else {
                $file->setInReplyTo($reply_to);
            }
        }

        $c = new FileController();
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
        $criterias['userid'] = $user->getId();
        return $this->getFiles($criterias);
    }

    public function getFiles($criterias = array())
    {
        $em = $this->getDoctrineManager();
        $repo = $em->getRepository('BisonLabSakonninBundle:File');
        $query = $repo->createQueryBuilder('f');

        if (isset($criterias['userid'])) {
            $query->where('f.createdBy = :userid');
            $query->setParameter('userid', $criterias['userid']);
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
        $repo = $em->getRepository('BisonLabSakonninBundle:FileContext');
        return $repo->contextHasFiles($context);
    }
}
