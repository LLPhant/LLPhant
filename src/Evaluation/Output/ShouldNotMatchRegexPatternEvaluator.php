<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\EvaluationResults;

class ShouldNotMatchRegexPatternEvaluator extends ShouldMatchRegexPatternEvaluator
{
    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        $out = parent::evaluateText($candidate, $reference, $n);
        $results = $out->getResults();

        return new EvaluationResults(
            'Regex pattern should not match evaluator',
            [
                'score' => (int) ! $results['score'],
                'error' => $results['score'] ? "Regex pattern {$this->regexPattern} matches text: {$candidate}." : '',
            ]
        );
    }
}
