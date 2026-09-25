<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Chat\Message;
use LLPhant\Evaluation\PairwiseString\PairwiseStringEvaluator;
use LLPhant\Evaluation\StringComparison\StringComparisonEvaluator;

it('can compare 2 candidates texts with reference text', function (): void {
    $candidateA = 'this is the way cookie is crashed';
    $candidateB = "cookie doesn't crumble at all";

    $reference = "that's the way cookie crumbles";

    $results = (new PairwiseStringEvaluator(new StringComparisonEvaluator))
        ->evaluateText($candidateA, $candidateB, $reference);

    expect($results->getResults())->toBe([
        'candidate_with_higher_score' => 'A',
        'text_candidate_with_higher_score' => 'this is the way cookie is crashed',
        'metric_name' => 'String Comparison Evaluation: ROUGE, BLEU, METEOR',
        'score_name' => 'ROUGE_recall',
        'score_A' => 0.6,
        'score_B' => 0.2,
    ]);
});

it('can compare 2 candidates Messages with reference texts', function (): void {
    $candidatesA = [Message::assistant('this is the way cookie is crashed'), Message::assistant('foo bar')];
    $candidatesB = [Message::assistant("cookie doesn't crumble at all"), Message::assistant('foo bear')];

    $references = ["that's the way cookie crumbles", 'foo bar'];

    $results = (new PairwiseStringEvaluator(new StringComparisonEvaluator))
        ->evaluateMessages($candidatesA, $candidatesB, $references);

    expect($results->getResults())->toBe([
        '0_candidate_with_higher_score' => 'A',
        '0_text_candidate_with_higher_score' => 'this is the way cookie is crashed',
        '0_metric_name' => 'String Comparison Evaluation: ROUGE, BLEU, METEOR',
        '0_score_name' => 'ROUGE_recall',
        '0_score_A' => 0.6,
        '0_score_B' => 0.2,
        '1_candidate_with_higher_score' => 'A',
        '1_text_candidate_with_higher_score' => 'foo bar',
        '1_metric_name' => 'String Comparison Evaluation: ROUGE, BLEU, METEOR',
        '1_score_name' => 'ROUGE_recall',
        '1_score_A' => 1.0,
        '1_score_B' => 0.5,
    ]);
});

it("throws exception when number of candidates and references don't match", function (): void {
    $candidatesA = [Message::assistant('this is the way cookie is crashed'), Message::assistant('foo bar')];
    $candidatesB = [Message::assistant("cookie doesn't crumble at all"), Message::assistant('foo bear')];

    $references = ["that's the way cookie crumbles"];

    (new PairwiseStringEvaluator(new StringComparisonEvaluator))
        ->evaluateMessages($candidatesA, $candidatesB, $references);
})->throws(\LogicException::class);
