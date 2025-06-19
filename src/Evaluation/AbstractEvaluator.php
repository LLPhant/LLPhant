<?php

namespace LLPhant\Evaluation;

use LLPhant\Query\SemanticSearch\ChatSession;

abstract class AbstractEvaluator implements EvaluatorInterface
{
    abstract public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults;

    abstract public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults;

    public function evaluateChatSession(ChatSession $chatSession, array $references = [], int $n = 1): EvaluationResults
    {
        return $this->evaluateMessages($chatSession->getHistory(), $references, $n);
    }
}
