<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Evaluation\Guardrails\Guardrails;
use LLPhant\Evaluation\Output\JSONFormatEvaluator;
use LLPhant\OpenAIConfig;

it('can use guardrails default response in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(
        llm: $llm,
        evaluator: new JSONFormatEvaluator(),
        strategy: Guardrails::STRATEGY_BLOCK,
    );

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('I\'m unable to answer your question right now.');
});

it('can use guardrails retry in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(
        llm: $llm,
        evaluator: new JSONFormatEvaluator(),
        strategy: Guardrails::STRATEGY_RETRY,
    );

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('{"correctKey":"correctVal"}');
});

it('can use guardrails callback in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(
        llm: $llm,
        evaluator: new JSONFormatEvaluator(),
        strategy: Guardrails::STRATEGY_CALLBACK,
        callback: fn (string $output, string $message): string => '{"correctKey":"defaultVal"}'
    );

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('{"correctKey":"defaultVal"}');
});

function getChatMock(): OpenAIChat
{
    return new class extends OpenAIChat
    {
        private readonly \Generator $generator;

        public function __construct()
        {
            $config = new OpenAIConfig();
            $config->apiKey = 'someKey';
            $this->generator = $this->generate();
            parent::__construct($config);
        }

        public function generateText(string $prompt): string
        {
            $val = $this->generator->current();
            $this->generator->next();

            return $val;
        }

        private function generate(): \Generator
        {
            foreach (['{incorrect json}', '{"correctKey":"correctVal"}'] as $answer) {
                yield $answer;
            }
        }
    };
}
