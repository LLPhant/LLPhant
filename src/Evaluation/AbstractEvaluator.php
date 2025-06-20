<?php

namespace LLPhant\Evaluation;

use LLPhant\Chat\Message;
use LLPhant\Query\SemanticSearch\ChatSession;

abstract class AbstractEvaluator implements EvaluatorInterface
{
    abstract public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults;

    abstract public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults;

    public function evaluateChatSession(ChatSession $chatSession, array $references = [], int $n = 1): EvaluationResults
    {
        return $this->evaluateMessages($chatSession->getHistory(), $references, $n);
    }

    /**
     * @param  Message[]  $messages
     * @return string[]
     */
    protected function filterAssistantMessages(array $messages): array
    {
        return array_values(array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'assistant' ? $message->content : null,
            $messages,
        )));
    }

    /**
     * @param  Message[]  $messages
     * @return string[]
     */
    protected function filterUserMessages(array $messages): array
    {
        return array_values(array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'user' ? $message->content : null,
            $messages,
        )));
    }
}
