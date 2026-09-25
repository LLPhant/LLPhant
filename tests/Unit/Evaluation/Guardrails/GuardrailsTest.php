<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Evaluation\Guardrails\Guardrails;
use LLPhant\Evaluation\Guardrails\GuardrailStrategy;
use LLPhant\Evaluation\Output\JSONFormatEvaluator;
use LLPhant\Evaluation\Output\NoFallbackAnswerEvaluator;
use LLPhant\Evaluation\Output\ShouldMatchRegexPatternEvaluator;
use LLPhant\Evaluation\Output\ShouldNotMatchRegexPatternEvaluator;
use LLPhant\Evaluation\Output\WordLimitEvaluator;
use LLPhant\OpenAIConfig;

it('can use guardrails default response in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(llm: $llm);
    $guardrails->addStrategy(new JSONFormatEvaluator(), GuardrailStrategy::STRATEGY_BLOCK);

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('I\'m unable to answer your question right now.');
});

it('can use guardrails retry in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(llm: $llm);
    $guardrails->addStrategy(new JSONFormatEvaluator(), GuardrailStrategy::STRATEGY_RETRY);

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('{"correctKey":"correctVal"}');
});

it('can use guardrails callback in case of incorrect answer', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(llm: $llm);
    $guardrails->addStrategy(
        evaluator: new JSONFormatEvaluator(),
        strategy: GuardrailStrategy::STRATEGY_CALLBACK,
        callback: fn (string $output, string $message): string => '{"correctKey":"defaultVal"}'
    );

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe('{"correctKey":"defaultVal"}');
});

it('can use multiple guardrails', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(llm: $llm);

    $guardrails->addStrategy(
        evaluator: new NoFallbackAnswerEvaluator(),
        strategy: GuardrailStrategy::STRATEGY_BLOCK
    );

    $guardrails->addStrategy(
        evaluator: (new WordLimitEvaluator())->setWordLimit(1),
        strategy: GuardrailStrategy::STRATEGY_BLOCK,
        defaultMessage: "I'm unable to answer your question right now."
    );

    $response = $guardrails->generateText('some prompt message');

    expect($response)->toBe("I'm unable to answer your question right now.");
});

it('can process message with multiple callbacks', function (): void {
    $llm = getChatMock();

    $guardrails = new Guardrails(llm: $llm);

    $guardrails->addStrategy(
        evaluator: new JSONFormatEvaluator(),
        strategy: GuardrailStrategy::STRATEGY_CALLBACK,
        callback: fn (string $output, string $message): string => '{"correctKey":"defaultVal"}'
    )->addStrategy(
        evaluator: (new ShouldMatchRegexPatternEvaluator())->setRegexPattern('/:\s/'),
        strategy: GuardrailStrategy::STRATEGY_CALLBACK,
        callback: fn (string $output, string $message): string => (string) preg_replace('/":"/', '": "', $output)
    )->addStrategy(
        evaluator: (new ShouldNotMatchRegexPatternEvaluator())->setRegexPattern('/[A-Z]/'),
        strategy: GuardrailStrategy::STRATEGY_CALLBACK,
        callback: fn (string $output, string $message): string => strtolower($output)
    );

    $response = $guardrails->generateText('generate correct JSON output...', returnAfterCallback: false);

    expect($response)->toBe('{"correctkey": "defaultval"}');
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
