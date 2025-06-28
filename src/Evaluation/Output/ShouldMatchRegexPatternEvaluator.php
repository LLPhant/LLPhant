<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

class ShouldMatchRegexPatternEvaluator extends AbstractEvaluator
{
    protected string $regexPattern = '';

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('Regex pattern evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Regex pattern evaluator doesn't support N-grams");
        }
        if ($this->regexPattern === '') {
            throw new \LogicException('specify regex pattern');
        }

        $out = preg_match($this->regexPattern, $candidate, $_);
        $result = (int) ($out > 0);

        return new EvaluationResults(
            'Regex pattern should match evaluator',
            [
                'score' => $result,
                'error' => $result !== 0 ? '' : "Regex pattern {$this->regexPattern} doesn't match text: {$candidate}.",
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('Regex pattern evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Regex pattern evaluator doesn't support N-grams");
        }
        $filteredMessages = $this->filterAssistantMessages($messages);
        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'Regex pattern evaluator',
            $resultsAll
        );
    }

    public function setRegexPattern(string $regexPattern): ShouldMatchRegexPatternEvaluator
    {
        $this->regexPattern = $regexPattern;

        return $this;
    }
}
