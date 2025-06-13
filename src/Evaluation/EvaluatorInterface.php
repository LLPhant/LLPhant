<?php

namespace LLPhant\Evaluation;

use LLPhant\Chat\Message;
use LLPhant\Query\SemanticSearch\ChatSession;

interface EvaluatorInterface
{
    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults;

    /**
     * @param  Message[]  $messages
     * @param  string[]  $references
     */
    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults;

    /**
     * @param  string[]  $references
     */
    public function evaluateChatSession(ChatSession $chatSession, array $references = [], int $n = 1): EvaluationResults;
}
