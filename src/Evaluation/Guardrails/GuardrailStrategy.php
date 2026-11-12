<?php

namespace LLPhant\Evaluation\Guardrails;

use LLPhant\Evaluation\EvaluatorInterface;

class GuardrailStrategy
{
    final public const string STRATEGY_BLOCK = 'block';

    final public const string STRATEGY_RETRY = 'retry';

    final public const string STRATEGY_CALLBACK = 'callback';

    /** @var callable|null */
    private $callback;

    public function __construct(private readonly EvaluatorInterface $evaluator, private readonly string $strategy, ?callable $callback, private readonly string $defaultMessage)
    {
        $this->callback = $callback;
    }

    public function getEvaluator(): EvaluatorInterface
    {
        return $this->evaluator;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    public function getDefaultMessage(): string
    {
        return $this->defaultMessage;
    }
}
