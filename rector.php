<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanAnd\RepeatedAndNotEqualToNotInArrayRector;use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
    ]);

    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
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
        ExplicitBoolCompareRector::class,
        RecastingRemovalRector::class,
        DisallowedEmptyRuleFixerRector::class,
        FunctionFirstClassCallableRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        IfIssetToCoalescingRector::class,
        FunctionFirstClassCallableRector::class,
        RepeatedAndNotEqualToNotInArrayRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RecastingRemovalRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RepeatedAndNotEqualToNotInArrayRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RemoveAlwaysTrueIfConditionRector::class,
        UseIdenticalOverEqualWithSameTypeRector::class,
        FunctionFirstClassCallableRector::class,
        DisallowedEmptyRuleFixerRector::class,
        FunctionFirstClassCallableRector::class,
        FunctionFirstClassCallableRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        DisallowedEmptyRuleFixerRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RecastingRemovalRector::class,
        DisallowedEmptyRuleFixerRector::class,
        DisallowedEmptyRuleFixerRector::class,
        RecastingRemovalRector::class,
    ]);
};
