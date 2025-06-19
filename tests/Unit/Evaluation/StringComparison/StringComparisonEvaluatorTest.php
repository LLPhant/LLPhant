<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\StringComparison\StringComparisonEvaluator;

it('can count ROUGE metric precision recall and F1 score', function (): void {
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

it('can count BLEU metric score', function (): void {
    $reference = "that's the way cookie crumbles";
    $candidate = 'this is the way bla bla bla';

    $results = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate);
    $score = $results->getResults();
    $metricName = $results->getMetricName();

    expect($score)->toBe(['score' => 0.29])->and($metricName)->toBe('BLEU');
});

it('calculates BLEU bigram score', function (): void {
    $reference = "that's the way cookie crumbles";
    $candidate = 'this is the way...';

    $result = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate, 2);

    expect($result->getMetricName())->toBe('BLEU')
        ->and($result->getResults())->toBe(['score' => 0.0]);
});

it('handles candidate longer than reference (brevity penalty = 1)', function (): void {
    $reference = 'short sentence';
    $candidate = 'short sentence with extra tokens making it definitely longer than the reference itself';

    $results = (new StringComparisonEvaluator())->calculateBleu($reference, $candidate)->getResults();
    $score = reset($results);

    // Score must be a valid probability 0-1 and > 0 because some words match.
    expect($score)->toBeFloat()
        ->and($score)->toBeGreaterThan(0)
        ->and($score)->toBeLessThanOrEqual(1);
});

it('can count METEOR metric score', function (): void {
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

it('can compute all string comparison scores from text', function (): void {
    $reference = 'The quick brown fox jumps over the lazy dog';
    $candidate = 'The quick brown dog jumps over the lazy fox';

    $results = (new StringComparisonEvaluator())->evaluateText($candidate, $reference);
    $score = $results->getResults();

    expect($score)->toBe([
        'ROUGE_recall' => 1.0,
        'ROUGE_precision' => 1.0,
        'ROUGE_f1' => 1.0,
        'BLEU_score' => 1.0,
        'METEOR_score' => 0.96,
        'METEOR_precision' => 1,
        'METEOR_recall' => 1,
        'METEOR_chunks' => 4,
        'METEOR_penalty' => 0.04389574759945129,
        'METEOR_fMean' => 1.0,
    ]);
});

it('can compute all string comparison scores from messages', function (): void {
    $reference = 'The quick brown fox jumps over the lazy dog';
    $candidate = 'The quick brown dog jumps over the lazy fox';
    $candidateMessage = new Message();
    $candidateMessage->role = ChatRole::User;
    $candidateMessage->content = $candidate;

    $results = (new StringComparisonEvaluator())->evaluateMessages([$candidateMessage], [$reference]);
    $score = $results->getResults();

    expect($score)->toBe([
        '0_ROUGE_recall' => 1.0,
        '0_ROUGE_precision' => 1.0,
        '0_ROUGE_f1' => 1.0,
        '0_BLEU_score' => 1.0,
        '0_METEOR_score' => 0.96,
        '0_METEOR_precision' => 1,
        '0_METEOR_recall' => 1,
        '0_METEOR_chunks' => 4,
        '0_METEOR_penalty' => 0.04389574759945129,
        '0_METEOR_fMean' => 1.0,
    ]);
});
