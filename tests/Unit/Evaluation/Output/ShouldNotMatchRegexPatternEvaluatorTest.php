<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\Output\ShouldNotMatchRegexPatternEvaluator;

it('can evaluate regex pattern match a text', function (): void {
    $output = 'once upon a time pink elephant jumped over a table';

    $results = (new ShouldNotMatchRegexPatternEvaluator())->setRegexPattern('/pink elephant/')->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => 'Regex pattern /pink elephant/ matches text: once upon a time pink elephant jumped over a table.',
    ]);
});

it("can evaluate regex pattern doesn't match a text", function (): void {
    $output = 'once upon a time pink elephant jumped over a table';

    $results = (new ShouldNotMatchRegexPatternEvaluator())->setRegexPattern('/pink LLPhant/')->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 1,
        'error' => '',
    ]);
});
