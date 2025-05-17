<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\StringComparison\StringComparisonEvaluator;

it('can count ROUGE metric precision recall and F1 score', function () {
    $reference = "that's the way cookie crumbles";
    $candidate = 'this is the way cookie is crashed';

    $results = (new StringComparisonEvaluator())->calculateROUGE($reference, $candidate);
    $rougeScores = $results->getResults();

    expect($rougeScores)->toBe([
        'recall' => 0.60,
        'precision' => 0.43,
        'f1' => 0.50,
    ]);
});

it('can count BLEU metric score', function () {
    $reference = "that's the way cookie crumbles";
    $candidate = 'this is the way bla bla bla';

    $results = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate);
    $score = $results->getResults();
    $metricName = $results->getMetricName();

    expect($score)->toBe(['score' => 0.29])->and($metricName)->toBe('BLEU');
});

it('calculates BLEU bigram score', function () {
    $reference = "that's the way cookie crumbles";
    $candidate = 'this is the way...';

    $result = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate, 2);

    expect($result->getMetricName())->toBe('BLEU')
        ->and($result->getResults())->toBe(['score' => 0.0]);
});

it('handles candidate longer than reference (brevity penalty = 1)', function () {
    $reference = 'short sentence';
    $candidate = 'short sentence with extra tokens making it definitely longer than the reference itself';

    $results = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate)->getResults();
    $score = reset($results);

    // Score must be a valid probability 0-1 and > 0 because some words match.
    expect($score)->toBeFloat()
        ->and($score)->toBeGreaterThan(0)
        ->and($score)->toBeLessThanOrEqual(1);
});

it('can count METEOR metric score', function () {
    $reference = 'The quick brown fox jumps over the lazy dog';
    $candidate = 'The quick brown dog jumps over the lazy fox';

    $results = (new StringComparisonEvaluator())->calculateMETEOR($reference, $candidate);
    $score = $results->getResults();

    expect($score)->toBe([
        'score' => 0.96,
        'precision' => 1,
        'recall' => 1,
        'chunks' => 4,
        'penalty' => 0.04389574759945129,
        'fMean' => 1.0,
    ]);
});
