<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\EmbeddingDistance;

use LLPhant\Embeddings\Distances\Distance;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;
use RuntimeException;

/**
 * Scores how semantically close a candidate is to a reference,
 * using cosine-similarity on embedding vectors.
 */
final class EmbeddingDistanceEvaluator extends AbstractEvaluator
{
    public function __construct(
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
        private readonly Distance $distance,
    ) {
    }

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference === '') {
            throw new RuntimeException('EmbeddingDistanceEvaluator needs a non-empty $reference string.');
        }

        $candidateVector = $this->embeddingGenerator->embedText($candidate);
        $referenceVector = $this->embeddingGenerator->embedText($reference);

        $distanceValue = $this->distance->measure($candidateVector, $referenceVector);

        return new EvaluationResults(
            'Embedding Distance Evaluation with '.$this->distance::class,
            [
                'distance' => round($distanceValue, 3),
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        // Only score assistant outputs, mirroring StringComparisonEvaluator
        $assistantMessages = $this->filterAssistantMessages($messages);

        if (count($assistantMessages) !== count($references)) {
            throw new \LogicException('The number of assistant messages and reference strings must match.');
        }

        $results = [];
        foreach ($assistantMessages as $idx => $assistantMessage) {
            $single = $this->evaluateText($assistantMessage, $references[$idx], $n);
            foreach ($single->getResults() as $value) {
                $results[$idx] = $value;
            }
        }

        return new EvaluationResults('Embedding Distance Evaluation', $results);
    }
}
