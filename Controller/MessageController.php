<?php

namespace BisonLab\SakonninBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;


/**
 * Message controller.
 *
 * @Route("/{access}/message", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class MessageController extends CommonController
{

    /**
     * Lists all Message entities.
     *
     * @Route("/", name="message")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('BisonLabSakonninBundle:Message')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a Message entity.
     *
     * @Route("/{id}", name="message_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $access, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:Message')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Message entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }

    /**
     * Lists all Messages with that context.
     *
     * @Route("/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}", name="message_context_search")
     * @Method("GET")
     * @Template()
     */
    public function searchContextGetAction(Request $request, $access, $system, $object_name, $external_id)
    {
        // Not yet.
        $context_conf = $this->container->getParameter('app.contexts');
        $conf = $context_conf['BisonLabSakonninBundle']['Message'];
        $conf['entity'] = "BisonLabSakonninBundle:Message";
        $conf['show_template'] = "BisonLabSakonninBundle:Message:show.html.twig";
        $conf['list_template'] = "BisonLabSakonninBundle:Message:index.html.twig";
dump($conf);
error_log("$access, $system, $object_name, $external_id");
        return $this->contextGetAction(
                    $request, $conf, $access, $system, $object_name, $external_id);

    }

}
