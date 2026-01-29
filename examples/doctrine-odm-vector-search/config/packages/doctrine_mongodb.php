<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return [
    'doctrine_mongodb' => [
        'auto_generate_proxy_classes' => true,
        'auto_generate_hydrator_classes' => true,
        'connections' => [
            'default' => [
                'server' => '%env(MONGODB_URI)%',
                'options' => [],
            ],
        ],
        'default_database' => '%env(MONGODB_DB)%',
        'document_managers' => [
            'default' => [
                'auto_mapping' => true,
                'mappings' => [
                    'App' => [
                        'dir' => '%kernel.project_dir%/src/Document',
                        'prefix' => 'App\Document',
                    ],
                ],
            ],
        ],
    ],
];
