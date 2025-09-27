<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Privatization\Rector\Class_\ChangeGlobalVariablesToPropertiesRector;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ArrayShapeFromConstantArrayReturnRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
    ]);

    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ChangeGlobalVariablesToPropertiesRector::class,
        ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class,
        ChangeReadOnlyVariableWithDefaultValueToConstantRector::class,
        PrivatizeLocalGetterToPropertyRector::class,
        PrivatizeFinalClassPropertyRector::class,
        PrivatizeFinalClassMethodRector::class,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->skip([
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        ArrayShapeFromConstantArrayReturnRector::class,
        AddDefaultValueForUndefinedVariableRector::class,
        ChangeAndIfToEarlyReturnRector::class,
        ChangeOrIfReturnToEarlyReturnRector::class,
        ExplicitBoolCompareRector::class
    ]);
};
