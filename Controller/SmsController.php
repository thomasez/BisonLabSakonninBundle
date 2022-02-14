<?php

namespace BisonLab\SakonninBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Psr\Log\LoggerInterface;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;
use BisonLab\SakonninBundle\Service\SmsHandler;

/**
 * SMS receive controller.
 * It could be in the message controller, but I'd rather want to separate for
 * less messiness.
 *
 * The main issue here is authentication and authorization. Not yet decided on
 * how to handle that. Make sure you tie as much as possible down in the
 * applications firewall configuration.
 *
 * @Route("/sms")
 */
class SmsController extends CommonController
{
    /**
     * Tries it's best to handle whatever being thrown at it and forward the
     * content to the configured default receiver.
     *
     * But if it does not, make separate receivers below.
     *
     * @Route("/post", name="sms_create", methods={"POST"})
     */
    public function createAction(Request $request, SmsHandler $smsHandler)
    {
        $data = [];
        // Is it Json?
        if ($parsed = json_decode($request->getContent(), true)) {
            $data = array_merge($request->query->all(), $parsed);
        } else {
            // Nope, we'll gather GET and POST data instead:
            $data = array_merge($request->query->all(),
                $request->request->all());
        }

        $status = $smsHandler->receive($data);
        if ($status instanceOf Response)
            return $status;
        if (true === $status)
            return new Response("OK", Response::HTTP_OK);
        // If it's not OK; handle eventual error.
        return new Response($status['message'] ?? "Error", $status['errcode'] ?? 500);
    }
}
