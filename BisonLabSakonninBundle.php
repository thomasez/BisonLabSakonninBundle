<?php

namespace BisonLab\SakonninBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

class BisonLabSakonninBundle extends Bundle
{
    public function __toString() { return 'BisonLabSakonninBundle'; }

    public function boot()
    {
        // Do I need states? "Sendt", "Queued", "Deleted" (Should be deleted
        // then anyway.), "Read" and so on?
        // It's defined in the Message entity for now.
        // ExternalEntityConfig::setStatesConfig($this->container->getParameter('app.states')[(string)$this]);
        ExternalEntityConfig::setAddressTypesConfig($this->container->getParameter('sakonnin.address_types'));
        ExternalEntityConfig::setFileTypesConfig($this->container->getParameter('sakonnin.file_types'));
    }
}
