<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\StringComparison\Metric;

use LLPhant\Evaluation\EvaluationResults;

final class METEOR extends AbstractStringComparisonMetric
{
    public function __construct(
        /**
         * Weight of precision in the harmonic mean (typical value: 0.9).
         */
        private readonly float $alpha = 0.9,
        /**
         * Exponent of the fragmentation penalty (typical value: 3.0).
         */
        private readonly float $beta = 3.0,
        /**
         * Scaling factor of the fragmentation penalty (typical value: 0.5).
         */
        private readonly float $gamma = 0.5
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        // 1. Tokenise (simple lowercase + whitespace split for now)
        $refTokens = $this->tokenize($reference);
        $candTokens = $this->tokenize($candidate);

        // 2. Count *exact* unigram matches (multiset intersection)
        $matches = $this->countMatches($refTokens, $candTokens);

        if ($matches === 0) {
            return new EvaluationResults(
                $this->getMetricName(),
                [
                    'score' => 0.0,
                    'precision' => 0.0,
                    'recall' => 0.0,
                    'chunks' => 0,
                ]
            );
        }

        // 3. Precision / recall
        $precision = $matches / \count($candTokens);
        $recall = $matches / \count($refTokens);

        // 4. Harmonic mean (called F‑mean in the paper)
        $fMean = $this->fMean($precision, $recall);

        // 5. Fragmentation penalty (chunk count based)
        $chunks = $this->countChunks($refTokens, $candTokens);
        $penalty = $this->gamma * ($chunks / $matches) ** $this->beta;

        // 6. Final score
        $score = round($fMean * (1.0 - $penalty), 2);

        return new EvaluationResults(
            $this->getMetricName(),
            [
                'score' => $score,
                'precision' => $precision,
                'recall' => $recall,
                'chunks' => $chunks,
                'penalty' => $penalty,
                'fMean' => $fMean,
            ]
        );
    }

    /* --------------------------------------------------------------------- */
    /* Helper methods                                                        */
    /* --------------------------------------------------------------------- */

    /**
     * Lower‑cases and splits a string on whitespace (UTF‑8 safe).
     * Override for custom tokenization / stemming.
     *
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        // Normalise line breaks, trim, convert to lower‑case.
        $text = \mb_strtolower(\preg_replace('/\R/u', ' ', $text) ?? '');

        return \preg_split('/\s+/u', \trim($text)) ?: [];
    }

    /**
     * Multiset intersection size (number of matching unigrams).
     *
     * @param  string[]  $refTokens
     * @param  string[]  $candTokens
     */
    private function countMatches(array $refTokens, array $candTokens): int
    {
        $matches = 0;
        $refFreq = \array_count_values($refTokens);

        foreach ($candTokens as $token) {
            if (($refFreq[$token] ?? 0) > 0) {
                $matches++;
                $refFreq[$token]--;
            }
        }

        return $matches;
    }

    /**
     * Counts the number of *contiguous* matching chunks in the candidate→reference alignment.
     *
     * @param  string[]  $refTokens
     * @param  string[]  $candTokens
     */
    private function countChunks(array $refTokens, array $candTokens): int
    {
        // Build mapping from token→list of reference positions
        $positionMap = [];
        foreach ($refTokens as $idx => $token) {
            $positionMap[$token][] = $idx;
        }

        $chunks = 0;
        $prevPos = -2; // ensure first match starts a new chunk

        foreach ($candTokens as $token) {
            if (! isset($positionMap[$token])) {
                continue;
            }
            if ($positionMap[$token] == []) {
                continue;
            }
            $pos = array_shift($positionMap[$token]); // first unused occurrence

            if ($pos != $prevPos + 1) {
                $chunks++;
            }
            $prevPos = $pos;
        }

        return $chunks;
    }

    /**
     * Weighted harmonic mean as defined in METEOR (alpha defaults to 0.9).
     */
    private function fMean(float $precision, float $recall): float
    {
        // Avoid division by zero
        if ($precision === 0.0) {
            return 0.0;
        }
        if ($recall === 0.0) {
            return 0.0;
        }

        return ($precision * $recall) / (($this->alpha * $precision) + ((1.0 - $this->alpha) * $recall));
    }

    private function getMetricName(): string
    {
        return 'METEOR';
    }
}
