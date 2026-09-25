<?php

declare(strict_types=1);

namespace Tests\Unit\Chat\Function;

use LLPhant\Chat\FunctionInfo\FunctionBuilder;

it('creates a functionInfo instance from a random class method', function () {
    $functionInfo = FunctionBuilder::buildFunctionInfo(new RichExample(), 'example');

    expect($functionInfo->name)->toBe('example');
    expect($functionInfo->description)->toBe('This is the description of the example function from the RichExample class.');
    expect($functionInfo->parameters[0]->name)->toBe('stringVar');
    expect($functionInfo->parameters[0]->type)->toBe('string');
});

it('creates a functionInfo instance from a method with complete docblock', function () {
    $functionInfo = FunctionBuilder::buildFunctionInfo(new RichExample(), 'example');

    expect($functionInfo->name)->toBe('example');
    expect($functionInfo->description)->toBe('This is the description of the example function from the RichExample class.');
    expect($functionInfo->parameters)->toHaveCount(5);

    expect($functionInfo->parameters[0]->description)->toBe('This is the description of the stringVar parameter.');
    expect($functionInfo->parameters[1]->description)->toBe('This is the description of the intVar parameter.');
    expect($functionInfo->parameters[2]->description)->toBe('This is the description of the floatVar parameter.');
    expect($functionInfo->parameters[3]->description)->toBe('This is the description of the boolVar parameter.');
    expect($functionInfo->parameters[4]->description)->toBe('This is the description of the arrayVar parameter.');
});

it('creates a functionInfo instance from a method without parameters in docblock', function () {
    $functionInfo = FunctionBuilder::buildFunctionInfo(new RichExample(), 'exampleWithNoPhpDocForParameters');

    expect($functionInfo->name)->toBe('exampleWithNoPhpDocForParameters');
    expect($functionInfo->description)->toBe('This is the description of the example function from the RichExample class.');
    expect($functionInfo->parameters)->toHaveCount(5);

    expect($functionInfo->parameters[0]->description)->toBe('');
    expect($functionInfo->parameters[1]->description)->toBe('');
    expect($functionInfo->parameters[2]->description)->toBe('');
    expect($functionInfo->parameters[3]->description)->toBe('');
    expect($functionInfo->parameters[4]->description)->toBe('');
});

it('handles functions without parameters', function () {
    $functionInfo = FunctionBuilder::buildFunctionInfo(new RichExample(), 'exampleWithNoParametersAndNoPhpdoc');

    expect($functionInfo->name)->toBe('exampleWithNoParametersAndNoPhpdoc');
    expect($functionInfo->description)->toBe('');
    expect($functionInfo->parameters)->toBeEmpty();
});
