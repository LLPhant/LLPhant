<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Exception\MissingParameterException;
use LLPhant\HeabsyConfig;

afterEach(function () {
    putenv('HEABSY_API_KEY');
    putenv('HEABSY_MODEL');
    unset(
        $_ENV['HEABSY_API_KEY'],
        $_ENV['HEABSY_MODEL'],
        $_SERVER['HEABSY_API_KEY'],
        $_SERVER['HEABSY_MODEL']
    );
});

it('uses heabsy defaults', function () {
    $config = new HeabsyConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://api.heabsy.com/v1')
        ->and($config->model)->toBe('qwen38');
});

it('reads api key from environment', function () {
    putenv('HEABSY_API_KEY=env-key');

    $config = new HeabsyConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('does not fall back to OPENAI_API_KEY', function () {
    $openAiApiKey = getenv('OPENAI_API_KEY');
    putenv('OPENAI_API_KEY=openai-key');

    try {
        $config = new HeabsyConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new OpenAIChat($config))->toThrow(MissingParameterException::class);
    } finally {
        putenv($openAiApiKey === false ? 'OPENAI_API_KEY' : 'OPENAI_API_KEY='.$openAiApiKey);
    }
});

it('reads model from environment', function () {
    putenv('HEABSY_MODEL=custom-model');

    $config = new HeabsyConfig('test-key');

    expect($config->model)->toBe('custom-model');
});

it('falls back to the default model when HEABSY_MODEL is set but empty', function () {
    putenv('HEABSY_MODEL=');

    $config = new HeabsyConfig('test-key');

    expect($config->model)->toBe('qwen38');
});

it('allows overriding url, model and model options', function () {
    $config = new HeabsyConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'custom-model',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('custom-model')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
