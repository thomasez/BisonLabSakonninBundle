<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use BisonLab\SakonninBundle\Entity\SakonninTemplate;
use BisonLab\SakonninBundle\Controller\SakonninTemplateController;

/**
 * Templates service.
 */
class Templates
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function getTemplate($name)
    {
        $em = $this->getDoctrineManager();
        if (is_numeric($name))
            $template = $em->getRepository('BisonLabSakonninBundle:SakonninTemplate')->find($name);
        else
            $template = $em->getRepository('BisonLabSakonninBundle:SakonninTemplate')->findOneByName($name);
        return $template;
    }

    /*
     * Should I bother having this one?
     * Not sure "Put everything in a service" is a fad any more.
     * But the options is the key here. It's for futureproofing and reminding
     * me why I have this.
     * (Why you say? May be that I add contexts or some logging to it all.)
     */
    public function storeTemplate(SakonninTemplate $template, array $options)
    {
        $em = $this->getDoctrineManager();
        $em->persist($template);
        return $template;
    }

    public function parse($template, $template_data = array(), $options = array())
    {
        $debug = isset($options['debug']) ? true : false;
        $sloader = new \Twig_Loader_Array(['message_template' => $template]);
        $loader = new \Twig_Loader_Chain(array($sloader));
        $twig = new \Twig_Environment($loader, array(
            'debug' => $debug, 
            'strict_variables' => isset($options['strict_variables']),
        ));
        $bin2hex_filter = new \Twig_SimpleFilter('bin2hex', 'bin2hex');
        $twig->addFilter($bin2hex_filter);
        $twig->addExtension(new \Twig_Extension_StringLoader());
        if ($debug) {
            $profile = new \Twig_Profiler_Profile();
            $twig->addExtension(new \Twig_Extension_Profiler($profile));
        }

        // I wonder if this hack works..
        // (It does. And I like it.)
        $template_data['configparser'] = $this;

        $parsed = $twig->render('message_template', $template_data);
        // First, just strip whitespaces.
        // First, just strip whitespaces.
        if (isset($options['strip_empty_lines'])) {
            // Not running per line means we have to strip away the newline and
            // linefeed aswell.
            $parsed = preg_replace('/^[ \t]*[\r\n]+/m', '', $parsed);
        }
        if (isset($options['strip_multiple_empty_lines'])) {
            // Not running per line means we have to strip away the newline and
            // linefeed aswell.
            $parsed = preg_replace('/^[ \t]*[\r\n]/m', "\n", $parsed);
            $parsed = preg_replace('/^[\r\n]{2,}/m', "\n", $parsed);
        }
        if ($debug) {
            $dumper = new \Twig_Profiler_Dumper_Text();
            $this->profiled = $dumper->dump($profile);
        }
        return $parsed;
    }
}
