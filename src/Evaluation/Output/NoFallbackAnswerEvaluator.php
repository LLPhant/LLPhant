<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

class NoFallbackAnswerEvaluator extends AbstractEvaluator
{
    /** @const string[] */
    private const FALLBACK_INDICATORS = [
        'I\'m sorry',
        'I cannot help',
        'I have no information',
        'please provide more details',
        'I need more information',
        'can you clarify',
        'answer some additional questions',
        'as an AI language model',
        'unfortunately, I',
        'unable to assist',
    ];

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('Fallback evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Fallback evaluator doesn't support N-grams");
        }

        $score = 1;
        $detectedIndicator = '';
        foreach (self::FALLBACK_INDICATORS as $indicator) {
            if (stripos(mb_strtolower($candidate), mb_strtolower($indicator)) !== false) {
                $score = 0;
                $detectedIndicator = $indicator;
                break;
            }
        }

        return new EvaluationResults(
            'No fallback response evaluator',
            [
                'score' => $score,
                'detectedIndicator' => $detectedIndicator,
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('Fallback evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Fallback evaluator doesn't support N-grams");
        }

        $filteredMessages = $this->filterAssistantMessages($messages);

        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'Fallback response evaluator',
            $resultsAll
        );
    }
}
