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
    protected $sakonninMessages;
    protected $options;

    public function __construct(
        private ServiceLocator $locator,
        private ParameterBagInterface $parameterBag
    ) {
        $this->locator = $locator;
        $options = $parameterBag->get('sakonnin.sms');
        if (isset($options['sender']))
            $this->setSender($options['sender']);
        if (isset($options['receiver']))
            $this->setReceiver($options['receiver']);
    }

    /*
     * Yeah, looking odd, but some times you just want to change the sender and
     * reciever in the middle of a request/job/run.
     *
     * Typically for just disabling sendingo anything just there and then.
     * And I'll do the same with receiver to keep consistency.
     */
    public function setSender($sender)
    {
        foreach ($this->locator->getProvidedServices() as $sclass) {
            $smshandler = $this->locator->get($sclass);
            $config = $smshandler->getConfig();
            if ($config['sends'] && $config['name'] == $sender) {
                $this->sender = $smshandler;
            }
        }
        if (!$this->sender)
            throw new \InvalidArgumentException("The SMS sender specified does not exist.");
    }

    public function setReceiver($receiver)
    {
        foreach ($this->locator->getProvidedServices() as $sclass) {
            if ($config['receives'] && $config['name'] == $receiver) {
                $this->receiver = $smshandler;
            }
        }
        if (!$this->receiver)
            throw new \InvalidArgumentException("The SMS sender specified does not exist.");
    }

    /*
     * This is sooo bloody bad.
     */
    public function setSakonninMessages($sakonninMessages)
    {
        $this->sakonninMessages = $sakonninMessages;
    }

    public function send($message, $receivers)
    {
        if ($this->sender) {
            // This I annoyingly need.
            $this->sender->setSakonninMessages($this->sakonninMessages);
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
            $this->receiver->setSakonninMessages($this->sakonninMessages);
            return $this->receiver->receive($data);
        } else {
            throw new \InvalidArgumentException("Cannot handle SMS reception because no method set");
        }
    }
}
