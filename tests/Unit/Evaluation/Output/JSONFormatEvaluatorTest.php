<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\Output\JSONFormatEvaluator;

it('can evaluate valid JSON response', function (): void {
    $output = '{"someKey": 1, "otherKey":"LLM", "anotherKey":[1, 2, 3, {"nestedKey":{"moreNestedKey":"moreNestedVal"}}]}';

    $results = (new JSONFormatEvaluator)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 1,
        'error' => '',
    ]);
});

it('can evaluate invalid JSON response', function (): void {
    $output = '{"someKey": 1, "otherKey":"LLM here is missing close parenthesis}';

    $results = (new JSONFormatEvaluator)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => 'Unexpected control character found',
    ]);
});

it('can evaluate valid JSON response with unexpected text before', function (): void {
    $output = 'Here is JSON output: {"someKey": 1, "otherKey":"LLM", "anotherKey":[1, 2, 3]}';

    $results = (new JSONFormatEvaluator)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => 'Syntax error, malformed JSON',
    ]);
});
