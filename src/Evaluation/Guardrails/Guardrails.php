<?php

namespace LLPhant\Evaluation\Guardrails;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\AbstractEvaluator;

class Guardrails
{
    final public const STRATEGY_BLOCK = 'block';

    final public const STRATEGY_RETRY = 'retry';

    final public const STRATEGY_CALLBACK = 'callback';

    /** @var callable|null */
    private $callback;

    public function __construct(
        private readonly ChatInterface $llm,
        private readonly AbstractEvaluator $evaluator,
        private readonly string $strategy = self::STRATEGY_BLOCK,
        ?callable $callback = null,
        private readonly string $defaultMessage = "I'm unable to answer your question right now."
    ) {
        $this->callback = $callback;
        if ($this->strategy !== self::STRATEGY_CALLBACK) {
            return;
        }
        if ($this->callback !== null) {
            return;
        }
        throw new \LogicException('Missing callback function.');
    }

    public function generateText(string $message): string
    {
        $response = $this->llm->generateText($message);
        $score = $this->evaluator
            ->evaluateMessages([Message::user($message), Message::assistant($response)])
            ->getResults();

        $scoreValue = (int) array_values($score)[0];

        if ($scoreValue === 1) {
            return $response;
        }

        return match ($this->strategy) {
            self::STRATEGY_BLOCK => $this->defaultMessage,
            self::STRATEGY_RETRY => $this->llm->generateText($message),
            self::STRATEGY_CALLBACK => $this->callback ? call_user_func($this->callback, $response, $message) : '',
            default => throw new \InvalidArgumentException("Unknown strategy: {$this->strategy}"),
        };
    }
}
