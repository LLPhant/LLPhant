<?php

use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMVectorStore;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->load('App\\', __DIR__.'/../src/');

    $services->set(DoctrineODMVectorStore::class)
        ->arg('$documentManager', service('doctrine_mongodb.odm.document_manager'))
        ->arg('$documentClassName', 'App\\Document\\Listing');
};
