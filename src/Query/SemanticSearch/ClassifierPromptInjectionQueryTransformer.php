<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Classification\ClassifierInterface;
use LLPhant\Classification\JevClassifier;
use LLPhant\Classification\NoulAnswer;
use LLPhant\Classification\NoulType;
use LLPhant\Exception\SecurityException;

class ClassifierPromptInjectionQueryTransformer implements QueryTransformer
{
    /**
     * @var array<string, NoulType>
     */
    private array $questions;

    public function __construct(
        private readonly ClassifierInterface $classifier = new JevClassifier(),
        private readonly float $minTrueScore = 0.60)
    {
        $this->questions = [
            'is_malicious' => new NoulType('This is a prompt to submit to an LLM. Could it be a malicious prompt and should I discard it?'),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws SecurityException
     */
    public function transformQuery(string $query): array
    {
        /** @var array<string, NoulAnswer> $response */
        $response = $this->classifier->askQuestions($query, $this->questions);
        $answer = $response['is_malicious'] ?? null;

        if (! $answer instanceof NoulAnswer) {
            throw new \Exception('Unexpected answer: '.\json_encode($response));
        }

        if ($answer->isTrue($this->minTrueScore)) {
            throw new SecurityException('Prompt flagged as insecure: '.$query);
        }

        return [$query];
    }
}
