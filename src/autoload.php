<?php

require_once __DIR__.'/vendor/Symfony/src/Symfony/Component/HttpFoundation/UniversalClassLoader.php';

$loader = new Symfony\Component\HttpFoundation\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'                   => __DIR__.'/vendor/Symfony/src',
    'Application'               => __DIR__,
    'Bundle'                    => __DIR__,
    'Doctrine\DBAL\Migrations'  => __DIR__.'/vendor/DoctrineMigrations/lib',
    'Doctrine\Common'           => __DIR__.'/vendor/Doctrine/lib/vendor/doctrine-common/lib',
    'Doctrine\\ODM\\MongoDB'    => __DIR__.'/vendor/doctrine-mongodb/lib',
    'Doctrine\DBAL'             => __DIR__.'/vendor/Doctrine/lib/vendor/doctrine-dbal/lib',
    'Doctrine'                  => __DIR__.'/vendor/Doctrine/lib',
    'Zend'                      => __DIR__.'/vendor/Zend/library',
));
$loader->register();

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__.'/vendor/Zend/library');
