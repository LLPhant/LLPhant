<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\Output\XMLFormatEvaluator;

it('can evaluate valid XML response', function (): void {
    $output = '<sometag>some content</sometag>';

    $results = (new XMLFormatEvaluator())->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 1,
        'error' => '',
    ]);
});

it('can evaluate invalid XML response', function (): void {
    $output = '<sometag>some content and missing closing tag';

    $results = (new XMLFormatEvaluator())->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => '[{"level":3,"code":77,"column":46,"message":"Premature end of data in tag sometag line 1\n","file":"","line":1}]',
    ]);
});

it('can evaluate valid XML response with unexpected text before', function (): void {
    $output = 'Here is XML output: <sometag>some content</sometag>';

    $results = (new XMLFormatEvaluator())->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => '[{"level":3,"code":4,"column":1,"message":"Start tag expected, \'<\' not found\n","file":"","line":1}]',
    ]);
});
