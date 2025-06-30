<?php

namespace LLPhant\Evaluation\Guardrails;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\EvaluatorInterface;

class Guardrails
{
    /** @var GuardrailStrategy[] */
    private array $strategies = [];

    public function __construct(
        private readonly ChatInterface $llm,
    ) {
    }

    public function generateText(string $message, int $maxRetry = 3, bool $returnAfterCallback = true): string
    {
        if ($this->strategies === []) {
            throw new \LogicException('need to specify guardrails strategies');
        }
        $response = $this->llm->generateText($message);
        while ($strategy = array_shift($this->strategies)) {
            $score = $strategy->getEvaluator()
                ->evaluateMessages([Message::user($message), Message::assistant($response)])
                ->getResults();

            $scoreValue = (int) array_values($score)[0];

            if ($scoreValue === 1) {
                $maxRetry = 3;

                continue;
            }

            if ($strategy->getStrategy() === GuardrailStrategy::STRATEGY_RETRY) {
                if (--$maxRetry === 0) {
                    return $strategy->getDefaultMessage();
                }
                array_unshift($this->strategies, $strategy);

                return $this->generateText($message, $maxRetry, $returnAfterCallback);
            }

            if ($strategy->getStrategy() === GuardrailStrategy::STRATEGY_BLOCK) {
                return $strategy->getDefaultMessage();
            }

            if ($strategy->getStrategy() === GuardrailStrategy::STRATEGY_CALLBACK) {
                if ($strategy->getCallback() === null) {
                    throw new \LogicException('missing callback function');
                }
                $response = call_user_func($strategy->getCallback(), $response, $message);
                if ($returnAfterCallback) {
                    return $response;
                }
                if ($this->strategies === []) {
                    return $response;
                }

                continue;
            }

            throw new \InvalidArgumentException("Unknown strategy: {$strategy->getStrategy()}");
        }

        return $response;
    }

    public function addStrategy(
        EvaluatorInterface $evaluator,
        string $strategy = GuardrailStrategy::STRATEGY_RETRY,
        ?callable $callback = null,
        string $defaultMessage = "I'm unable to answer your question right now."
    ): self {
        if ($strategy === GuardrailStrategy::STRATEGY_CALLBACK && $callback === null) {
            throw new \LogicException('Missing callback function.');
        }
        $this->strategies[] = new GuardrailStrategy(
            $evaluator,
            $strategy,
            $callback,
            $defaultMessage
        );

        return $this;
    }
}
