<?php

declare(strict_types=1);

namespace Tests\Unit\Tool;

use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Tool\HumanInTheLoopTool;

it('returns the answer provided by the injected input callable', function (): void {
    $tool = new HumanInTheLoopTool(false, fn (string $q): string => 'Paris');

    $answer = $tool->askUser('What is the capital of France?');

    expect($answer)->toBe('Paris');
});

it('stores the answer in lastResponse and sets wasSuccessful to true', function (): void {
    $tool = new HumanInTheLoopTool(false, fn (string $q): string => 'blue');

    $tool->askUser('What is your favourite colour?');

    expect($tool->lastResponse)->toBe('blue');
    expect($tool->wasSuccessful)->toBeTrue();
});

it('passes the question to the input callable', function (): void {
    $receivedQuestion = null;
    $tool = new HumanInTheLoopTool(false, function (string $q) use (&$receivedQuestion): string {
        $receivedQuestion = $q;

        return 'answer';
    });

    $tool->askUser('How old are you?');

    expect($receivedQuestion)->toBe('How old are you?');
});

it('can be used with FunctionBuilder to produce a FunctionInfo', function (): void {
    $tool = new HumanInTheLoopTool(false, fn (string $q): string => 'yes');

    $functionInfo = FunctionBuilder::buildFunctionInfo($tool, 'askUser');

    expect($functionInfo->name)->toBe('askUser');
    expect($functionInfo->parameters)->toHaveCount(1);
    expect($functionInfo->parameters[0]->name)->toBe('question');
    expect($functionInfo->parameters[0]->type)->toBe('string');
    expect($functionInfo->requiredParameters)->toHaveCount(1);
});
