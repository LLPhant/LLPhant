<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;
use Yethee\Tiktoken\EncoderProvider;

class TokenLimitEvaluator extends AbstractEvaluator
{
    private string $provider = 'cl100k_base';

    private int $tokenLimit = 0;

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('Token limit evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Token limit evaluator doesn't support N-grams");
        }
        if ($this->tokenLimit === 0) {
            throw new \LogicException('use TokenLimitEvaluator::setTokenLimit to specify token limit');
        }

        $provider = new EncoderProvider();
        $encoder = $provider->get($this->provider); // @phpstan-ignore-line
        $tokens = $encoder->encode($candidate);
        $numTokens = count($tokens);

        $result = (int) ($numTokens < $this->tokenLimit);

        return new EvaluationResults(
            'Token limit evaluator',
            [
                'score' => $result,
                'error' => $result === 0 ? "Generated {$numTokens} tokens is grater than limit of {$this->tokenLimit}" : '',
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('Token limit evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Token limit evaluator doesn't support N-grams");
        }
        $filteredMessages = $this->filterAssistantMessages($messages);
        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'Token limit evaluator',
            $resultsAll
        );
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function setTokenLimit(int $tokenLimit): self
    {
        $this->tokenLimit = $tokenLimit;

        return $this;
    }
}
