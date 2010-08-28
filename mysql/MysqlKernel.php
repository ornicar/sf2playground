<?php

require_once __DIR__.'/../src/autoload.php';

use Symfony\Framework\Kernel;
use Symfony\Component\DependencyInjection\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MysqlKernel extends Kernel
{
    public function registerRootDir()
    {
        return __DIR__;
    }

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Framework\KernelBundle(),
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),

            // enable third-party bundles
            new Symfony\Bundle\ZendBundle\ZendBundle(),
            new Symfony\Bundle\DoctrineBundle\DoctrineBundle(),

            // register your bundles here
            new Bundle\MarkdownBundle\MarkdownBundle(),
            new Bundle\MenuBundle\MenuBundle(),
            new Bundle\DoctrineUserBundle\DoctrineUserBundle(),
            new Bundle\TestSessionBundle\TestSessionBundle(),
            new Bundle\OfflineBundle\OfflineBundle(),
            new Bundle\DoctrinePaginatorBundle\DoctrinePaginatorBundle(),
            new Bundle\ZendCacheBundle\ZendCacheBundle(),

            // register your applications here
            //new Application\Bundle\MysqlBundle()
        );

        return $bundles;
    }

    public function registerBundleDirs()
    {
        return array(
            'Application'        => __DIR__.'/../src/Application',
            'Bundle'             => __DIR__.'/../src/Bundle',
            'Symfony\\Bundle'    => __DIR__.'/../src/vendor/Symfony/src/Symfony/Bundle',
        );
    }

    /**
     * Returns the config_{environment}_local.yml file or 
     * the default config_{environment}.yml if it does not exist.
     * Useful to override development password.
     *
     * @param string Environment
     * @return The configuration file path
     */
    protected function getLocalConfigurationFile($environment)
    {
        $basePath = __DIR__.'/config/config_';
        $file = $basePath.$environment.'_local.yml';

        if(\file_exists($file))
        {
            return $file;
        }

        return $basePath.$environment.'.yml';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $container = new ContainerBuilder();

        $loader->load($this->getLocalConfigurationFile($this->getEnvironment()));

        return $container;
    }
}
