<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\StringComparison\Metric;

use LLPhant\Evaluation\EvaluationResults;

final class ROUGE extends AbstractStringComparisonMetric
{
    public function calculate(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        $candidateWords = explode(' ', $candidate);
        $referenceWords = explode(' ', $reference);

        $candidateNGrams = $this->getNGrams($candidateWords, $n);
        $referenceNGrams = $this->getNGrams($referenceWords, $n);

        $matches = 0;
        foreach ($candidateNGrams as $ngram) {
            if (in_array($ngram, $referenceNGrams)) {
                $matches++;
            }
        }

        $recall = $matches / max(count($referenceNGrams), 1);
        $precision = $matches / max(count($candidateNGrams), 1);
        $f1Score = ($recall + $precision > 0)
            ? 2 * ($recall * $precision) / ($recall + $precision)
            : 0;

        return new EvaluationResults(
            $this->getMetricName(),
            [
                'recall' => round($recall, 2),
                'precision' => round($precision, 2),
                'f1' => round($f1Score, 2),
            ]
        );
    }

    public function getMetricName(): string
    {
        return 'ROUGE';
    }
}
