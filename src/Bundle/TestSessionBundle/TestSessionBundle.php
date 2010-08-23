<?php

namespace Bundle\TestSessionBundle;

use Symfony\Framework\Bundle\Bundle as BaseBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestSessionBundle extends BaseBundle
{
    /**
     * @see ContainerInterface::shutdown
     */
    public function shutdown()
    {
        /**
         * On test environment, simulate session end to persist session attributes when kernel is rebooted
         */
        if('test' === $this->container->getParameter('kernel.environment')) {
            $this->container->getSessionService()->__destruct();
            $this->container->getSession_StorageService()->persist();
        }
    }
}
