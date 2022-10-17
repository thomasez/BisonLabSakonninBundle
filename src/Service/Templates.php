<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ChainLoader;
use Twig\Profiler\Profile as TwigProfile;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Dumper\TextDumper;

use BisonLab\SakonninBundle\Entity\SakonninTemplate;
use BisonLab\SakonninBundle\Controller\SakonninTemplateController;

/**
 * Templates service.
 */
class Templates
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    private $entityManager;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ParameterBagInterface $parameterBag,
        private TranslatorInterface $translator
    ) {
        $this->entityManager = $this->getDoctrineManager();
    }

    public function getTemplate($name)
    {
        // Just the id, ok.
        if (is_numeric($name))
            return $this->entityManager->getRepository(SakonninTemplate::class)->find($name);

        // Attempt #1
        $locale = $this->translator->getLocale();
        if ($template = $this->entityManager->getRepository(SakonninTemplate::class)->findOneBy(
            ['name' => $name, 'lang_code' => $locale]))
                return $template;

        // Attempt #2 - Fallback
        foreach ($this->translator->getFallbackLocales() as $fb) {
            if ($template = $this->entityManager->getRepository(SakonninTemplate::class)
                ->findOneBy(['name' => $name, 'lang_code' => $fb]))
                    return $template;
        }

        // Whatever!
        return $this->entityManager->getRepository(SakonninTemplate::class)->findOneBy(
            ['name' => $name]);
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
        $this->entityManager->persist($template);
        return $template;
    }

    public function parse($template, $template_data = array(), $options = array())
    {
        $twig_env_options = [];
        $debug = false;
        if (isset($options['debug'])) {
            $twig_env_options['debug'] = true;
            $debug = true;
        }
        if (isset($template_data['strict_variables']))
            $twig_env_options['strict_variables'] = true;
        // I have to have two ways to disable autoescape. Options kinda default
        // but overriding with template_data. (Annoying, yes)
        if (isset($options['no_autoescape']))
            $twig_env_options['autoescape'] = false;
        if (isset($template_data['no_autoescape']))
            $twig_env_options['autoescape'] = false;
        $sloader = new ArrayLoader(['message_template' => $template]);
        $loader = new ChainLoader(array($sloader));
        $twig = new TwigEnvironment($loader, $twig_env_options);
        $bin2hex_filter = new TwigFilter('bin2hex', 'bin2hex');
        $twig->addFilter($bin2hex_filter);
        $twig->addExtension(new StringLoaderExtension());
        if ($debug) {
            $profile = new TwigProfile();
            $twig->addExtension(new ProfilerExtension($profile));
        }

        // I wonder if this hack works..
        // (It does. And I like it.)
        $template_data['twigparser'] = $this;

        $parsed = $twig->render('message_template', $template_data);
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
            $dumper = new TextDumper();
            $this->profiled = $dumper->dump($profile);
        }
        return $parsed;
    }
}
