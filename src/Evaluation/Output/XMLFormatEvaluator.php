<?php

namespace LLPhant\Evaluation\Output;

use DOMDocument;
use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

class XMLFormatEvaluator extends AbstractEvaluator
{
    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($reference !== '') {
            throw new \LogicException('XML format evaluator takes only output text as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("XML format evaluator doesn't support N-grams");
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $isValidXML = (bool) $dom->loadXML($candidate, LIBXML_NOENT | LIBXML_NONET);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return new EvaluationResults(
            'XML valid format evaluator',
            [
                'score' => (int) $isValidXML,
                'error' => $errors !== [] ? json_encode($errors, JSON_THROW_ON_ERROR) : '',
            ]
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($references !== []) {
            throw new \LogicException('XML format evaluator takes only output texts as argument');
        }
        if ($n !== 1) {
            throw new \LogicException("XML format evaluator doesn't support N-grams");
        }
        $filteredMessages = $this->filterAssistantMessages($messages);
        $resultsAll = [];
        foreach ($filteredMessages as $filteredMessage) {
            $resultsSingle = $this->evaluateText($filteredMessage);
            $resultsAll[] = $resultsSingle->getResults()['score'];
        }

        return new EvaluationResults(
            'XML valid format evaluator',
            $resultsAll
        );
    }
}
