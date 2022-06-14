<?php

namespace BisonLab\SakonninBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

class BisonLabSakonninBundle extends Bundle
{
    public function __toString()
    {
        return 'BisonLabSakonninBundle';
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function boot()
    {
        // Do I need states? "Sendt", "Queued", "Deleted" (Should be deleted
        // then anyway.), "Read" and so on?
        // It's defined in the Message entity for now.
        ExternalEntityConfig::setStatesConfig($this->container->getParameter('sakonnin.states'));
        ExternalEntityConfig::setAddressTypesConfig($this->container->getParameter('sakonnin.address_types'));
        ExternalEntityConfig::setBaseTypesConfig($this->container->getParameter('sakonnin.base_types'));
        ExternalEntityConfig::setFileTypesConfig($this->container->getParameter('sakonnin.file_types'));
    }
}
