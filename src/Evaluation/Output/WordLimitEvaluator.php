<?php

namespace LLPhant\Evaluation\Output;

use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

class WordLimitEvaluator extends AbstractEvaluator
{
    private int $wordLimit = 0;

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('Word limit evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Word limit evaluator doesn't support N-grams");
        }
        if ($this->wordLimit === 0) {
            throw new \LogicException('use WordLimitEvaluator::setWordLimit to specify word limit');
        }

        $numWords = preg_match_all('/\p{L}+/u', $candidate, $_);
        $result = (int) ($numWords < $this->wordLimit);

        return new EvaluationResults(
            'Word limit  evaluator',
            [
                'score' => $result,
                'error' => $result === 0 ? "Generated {$numWords} words is grater than limit of {$this->wordLimit}" : '',
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('Word limit evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("Word limit evaluator doesn't support N-grams");
        }
        $filteredMessages = $this->filterAssistantMessages($messages);
        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'Word limit evaluator',
            $resultsAll
        );
    }

    public function setWordLimit(int $wordLimit): self
    {
        $this->wordLimit = $wordLimit;

        return $this;
    }
}
