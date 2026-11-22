<?php

namespace Tests\Unit\Chat;

use LLPhant\Chat\GeminiAIChat;
use LLPhant\Chat\MistralAIChat;
use LLPhant\Exception\MissingParameterException;
use LLPhant\GeminiOpenAIConfig;
use LLPhant\MistralAIConfig;

function withEnvironment(array $values, callable $callback): void
{
    $previous = [];
    foreach ($values as $name => $value) {
        $previous[$name] = [
            'process' => getenv($name),
            'env' => array_intersect_key($_ENV, [$name => true]),
            'server' => array_intersect_key($_SERVER, [$name => true]),
        ];
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($value === null ? $name : "$name=$value");
    }

    try {
        $callback();
    } finally {
        foreach ($previous as $name => $value) {
            $processValue = $value['process'];
            putenv($processValue === false ? $name : "$name=$processValue");
            unset($_ENV[$name], $_SERVER[$name]);
            $_ENV += $value['env'];
            $_SERVER += $value['server'];
        }
    }
}

it('asks for MISTRAL_API_KEY when no Mistral key is set', function () {
    withEnvironment(['MISTRAL_API_KEY' => null, 'OPENAI_API_KEY' => null], function () {
        expect(fn () => new MistralAIChat())
            ->toThrow(MissingParameterException::class, 'MISTRAL_API_KEY');
    });
});

it('does not use OPENAI_API_KEY for Mistral', function () {
    withEnvironment(['MISTRAL_API_KEY' => null, 'OPENAI_API_KEY' => 'openai-key'], function () {
        $config = new MistralAIConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new MistralAIChat($config))->toThrow(MissingParameterException::class, 'MISTRAL_API_KEY');
    });
});

it('asks for GEMINI_API_KEY when no Gemini key is set', function () {
    withEnvironment(['GEMINI_API_KEY' => null, 'OPENAI_API_KEY' => null], function () {
        expect(fn () => new GeminiAIChat())
            ->toThrow(MissingParameterException::class, 'GEMINI_API_KEY');
    });
});

it('does not use OPENAI_API_KEY for Gemini', function () {
    withEnvironment(['GEMINI_API_KEY' => null, 'OPENAI_API_KEY' => 'openai-key'], function () {
        $config = new GeminiOpenAIConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new GeminiAIChat($config))->toThrow(MissingParameterException::class, 'GEMINI_API_KEY');
    });
});
