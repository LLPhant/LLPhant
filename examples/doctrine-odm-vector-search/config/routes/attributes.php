<?php

use App\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import(__DIR__.'/../../src/Controller/', 'attribute');

    $routes->import(Kernel::class, 'attribute');
};
