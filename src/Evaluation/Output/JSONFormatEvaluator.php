<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

class JSONFormatEvaluator extends AbstractEvaluator
{
    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('JSON format evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("JSON format evaluator doesn't support N-grams");
        }

        json_decode($candidate);
        $validationResult = json_last_error();

        $error = match ($validationResult) {
            JSON_ERROR_NONE => '',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            default => 'Unknown error',
        };

        return new EvaluationResults(
            'JSON valid format evaluator',
            [
                'score' => (int) ($validationResult === JSON_ERROR_NONE),
                'error' => $error,
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('JSON format evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("JSON format evaluator doesn't support N-grams");
        }
        $filteredMessages = $this->filterAssistantMessages($messages);
        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'JSON valid format evaluator',
            $resultsAll
        );
    }
}
