<?php

namespace BridgeCert;

use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BridgeCert extends Plugin
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->getDefinition('shopware.openssl_verificator')
            ->setArguments([
                $this->getPath(). '/bridge.public'
            ]);
    }
}