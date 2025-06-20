<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\Output\NoFallbackAnswerEvaluator;

it('can detect fallback in response', function (): void {
    $output = 'sorry, I cannot help you';

    $results = (new NoFallbackAnswerEvaluator)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'detectedIndicator' => 'I cannot help',
    ]);
});

it('can evaluate response without fallback', function (): void {
    $output = 'Poznan is a city in Poland';

    $results = (new NoFallbackAnswerEvaluator)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 1,
        'detectedIndicator' => '',
    ]);
});
