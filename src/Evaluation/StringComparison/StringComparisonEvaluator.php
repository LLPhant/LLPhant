<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\StringComparison;

use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;
use LLPhant\Evaluation\StringComparison\Metric\BLEU;
use LLPhant\Evaluation\StringComparison\Metric\METEOR;
use LLPhant\Evaluation\StringComparison\Metric\ROUGE;

final class StringComparisonEvaluator extends AbstractEvaluator
{
    private readonly BLEU $bleu;

    private readonly ROUGE $rouge;

    private readonly METEOR $meteor;

    public function __construct()
    {
        $this->bleu = new BLEU();
        $this->rouge = new ROUGE();
        $this->meteor = new METEOR();
    }

    public function calculateBLEU(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->bleu->calculate($reference, $candidate, $n);
    }

    public function calculateROUGE(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->rouge->calculate($reference, $candidate, $n);
    }

    public function calculateMETEOR(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->meteor->calculate($reference, $candidate, $n);
    }

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        /** @var EvaluationResults[] $results */
        $results = [
            $this->rouge->calculate($reference, $candidate, $n),
            $this->bleu->calculate($reference, $candidate, $n),
            $this->meteor->calculate($reference, $candidate, $n),
        ];
        $resultsIndexed = [];
        foreach ($results as $result) {
            foreach ($result->getResults() as $metricName => $resultSingle) {
                $resultsIndexed[$result->getMetricName().'_'.$metricName] = $resultSingle;
            }
        }

        return new EvaluationResults(
            'String Comparison Evaluation: ROUGE, BLEU, METEOR',
            $resultsIndexed
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        $assistantMessages = array_filter(
            $messages,
            fn (Message $message): ChatRole => $message->role = ChatRole::Assistant
        );
        if (count($assistantMessages) !== count($references)) {
            throw new \LogicException('Number of assistant messages is different than number of references!');
        }

        $resultsArray = [];
        foreach ($assistantMessages as $idx => $assistantMessage) {
            $resultsSingle = $this->evaluateText($assistantMessage->content, $references[$idx], $n);
            foreach ($resultsSingle->getResults() as $key => $resultSingle) {
                $resultsArray[$idx.'_'.$key] = $resultSingle;
            }
        }

        return new EvaluationResults(
            'String Comparison Evaluation: ROUGE, BLEU, METEOR',
            $resultsArray
        );
    }
}
