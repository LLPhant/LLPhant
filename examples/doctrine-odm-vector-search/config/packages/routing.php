<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return [
    'framework' => [
        'router' => [
            'default_uri' => '%env(DEFAULT_URI)%',
        ],
    ],
];
