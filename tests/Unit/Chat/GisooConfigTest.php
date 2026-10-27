<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Exception\MissingParameterException;
use LLPhant\GisooConfig;

afterEach(function () {
    putenv('GISOO_API_KEY');
    putenv('GISOO_MODEL');
    unset(
        $_ENV['GISOO_API_KEY'],
        $_ENV['GISOO_MODEL'],
        $_SERVER['GISOO_API_KEY'],
        $_SERVER['GISOO_MODEL']
    );
});

it('uses gisoo defaults', function () {
    $config = new GisooConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://gisoo.pro/api/v1')
        ->and($config->model)->toBe('gpt-5-mini');
});

it('reads api key from environment', function () {
    putenv('GISOO_API_KEY=env-key');

    $config = new GisooConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('does not fall back to OPENAI_API_KEY', function () {
    $openAiApiKey = getenv('OPENAI_API_KEY');
    putenv('OPENAI_API_KEY=openai-key');

    try {
        $config = new GisooConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new OpenAIChat($config))->toThrow(MissingParameterException::class);
    } finally {
        putenv($openAiApiKey === false ? 'OPENAI_API_KEY' : 'OPENAI_API_KEY='.$openAiApiKey);
    }
});

it('reads model from environment', function () {
    putenv('GISOO_MODEL=custom-model');

    $config = new GisooConfig('test-key');

    expect($config->model)->toBe('custom-model');
});

it('falls back to the default model when GISOO_MODEL is set but empty', function () {
    putenv('GISOO_MODEL=');

    $config = new GisooConfig('test-key');

    expect($config->model)->toBe('gpt-5-mini');
});

it('allows overriding url, model and model options', function () {
    $config = new GisooConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'custom-model',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('custom-model')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
