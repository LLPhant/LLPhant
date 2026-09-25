<?php

declare(strict_types=1);

namespace Tests\Unit\Chat\Function;

use LLPhant\Chat\FunctionInfo\TypeMapper;
use ReflectionMethod;
use ReflectionNamedType;

it('gets the right types', function () {
    $reflection = new ReflectionMethod(RichExample::class, 'example');
    $params = $reflection->getParameters();

    /** @var ReflectionNamedType $stringType */
    $stringType = $params[0]->getType();
    $string = TypeMapper::mapPhpTypeToJsonSchemaType($stringType);
    expect($string)->toBe('string');

    /** @var ReflectionNamedType $intType */
    $intType = $params[1]->getType();
    $integer = TypeMapper::mapPhpTypeToJsonSchemaType($intType);
    expect($integer)->toBe('integer');

    /** @var ReflectionNamedType $floatType */
    $floatType = $params[2]->getType();
    $float = TypeMapper::mapPhpTypeToJsonSchemaType($floatType);
    expect($float)->toBe('number');

    /** @var ReflectionNamedType $boolType */
    $boolType = $params[3]->getType();
    $bool = TypeMapper::mapPhpTypeToJsonSchemaType($boolType);
    expect($bool)->toBe('boolean');

    /** @var ReflectionNamedType $arrayType */
    $arrayType = $params[4]->getType();
    $array = TypeMapper::mapPhpTypeToJsonSchemaType($arrayType);
    expect($array)->toBe('array');
});
