<?php

namespace BisonLab\SakonninBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/*
 * Handling ways to send and receive SMSes.
 * I could extend this to use external/custom thignies, but I'd rather make you
 * commit the method to this system :=)
 */
class SmsHandler
{
    protected $sender;
    protected $receiver;
    protected $options;

    public function __construct(
        private ServiceLocator $locator,
        private ParameterBagInterface $parameterBag
    ) {
        $this->locator = $locator;
        $options = $parameterBag->get('sakonnin.sms');
        $sender_name   = $options['sender'] ?? null;
        $receiver_name = $options['receiver'] ?? null;
        foreach ($this->locator->getProvidedServices() as $sclass) {
            $smshandler = $this->locator->get($sclass);
            $config = $smshandler->getConfig();
            if ($config['sends'] && $config['name'] == $sender_name) {
                $this->sender = $smshandler;
            }
            if ($config['receives'] && $config['name'] == $receiver_name) {
                $this->receiver = $smshandler;
            }
        }
    }

    public function send($message, $receivers)
    {
        if ($this->sender) {
            // This I annoyingly need.
            $sender->setSakonninMessages($this->sakonninMessages);
            return $this->sender->send($message, $receivers);
        } else {
            throw new \InvalidArgumentException("Cannot send SMS because no sender method set");
        }
    }

    /*
     * 
     */
    public function receive($data)
    {
        if ($this->receiver) {
            // This I annoyingly need.
            $sender->setSakonninMessages($this->sakonninMessages);
            return $this->receiver->receive($data);
        } else {
            throw new \InvalidArgumentException("Cannot handle SMS reception because no method set");
        }
    }
}
