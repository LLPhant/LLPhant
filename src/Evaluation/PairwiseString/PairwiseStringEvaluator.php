<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\PairwiseString;

use LLPhant\Chat\Message;
use LLPhant\Evaluation\EvaluationResults;
use LLPhant\Evaluation\EvaluatorInterface;

/**
 * Compares two strings and reports which is closer to the reference.
 */
final class PairwiseStringEvaluator
{
    public function __construct(
        private readonly EvaluatorInterface $evaluator
    ) {
    }

    public function evaluateText(string $candidateA, string $candidateB, string $reference): EvaluationResults
    {
        $resultsA = $this->evaluator->evaluateText($candidateA, $reference);
        $resultsB = $this->evaluator->evaluateText($candidateB, $reference);

        //first results value contains overall/representative score
        $scoresA = $resultsA->getResults();
        $overallScoreKey = array_keys($scoresA)[0];
        $overallScoreAValue = array_values($scoresA)[0];
        $scoresB = $resultsB->getResults();
        $overallScoreBValue = array_values($scoresB)[0];

        if ($overallScoreAValue === $overallScoreBValue) {
            $result = 'equal';
            $text = '';
        } elseif ($overallScoreAValue > $overallScoreBValue) {
            $result = 'A';
            $text = $candidateA;
        } else {
            $result = 'B';
            $text = $candidateB;
        }

        return new EvaluationResults(
            'Pairwise String Evaluation',
            [
                'candidate_with_higher_score' => $result,
                'text_candidate_with_higher_score' => $text,
                'metric_name' => $resultsA->getMetricName(),
                'score_name' => $overallScoreKey,
                'score_A' => $overallScoreAValue,
                'score_B' => $overallScoreBValue,
            ]
        );
    }

    /**
     * @param  Message[]  $messagesA
     * @param  Message[]  $messagesB
     * @param  string[]  $references
     */
    public function evaluateMessages(array $messagesA, array $messagesB, array $references = []): EvaluationResults
    {
        if (count($messagesA) !== count($messagesB)) {
            throw new \LogicException(
                'PairwiseStringEvaluator expects the same number of messages for both candidates.'
            );
        }

        if (count($messagesA) !== count($references)) {
            throw new \LogicException(
                'PairwiseStringEvaluator expects the same number of references as messages.'
            );
        }

        $resultsAll = [];
        foreach ($messagesA as $idx => $messageA) {
            $results = $this->evaluateText($messageA->content, $messagesB[$idx]->content, $references[$idx]);
            $resultsAll[] = $results->getResults();
        }

        $resultsFlatten = [];
        foreach ($resultsAll as $idx => $resultsSingle) {
            foreach ($resultsSingle as $key => $value) {
                $resultsFlatten[$idx.'_'.$key] = $value;
            }
        }

        return new EvaluationResults('Pairwise String Evaluation', $resultsFlatten);
    }
}
