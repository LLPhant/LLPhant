<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\CheaperInferenceConfig;
use LLPhant\Exception\MissingParameterException;

afterEach(function () {
    putenv('CHEAPER_INFERENCE_API_KEY');
    putenv('CHEAPER_INFERENCE_MODEL');
    unset(
        $_ENV['CHEAPER_INFERENCE_API_KEY'],
        $_ENV['CHEAPER_INFERENCE_MODEL'],
        $_SERVER['CHEAPER_INFERENCE_API_KEY'],
        $_SERVER['CHEAPER_INFERENCE_MODEL']
    );
});

it('uses cheaper inference defaults', function () {
    $config = new CheaperInferenceConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://api.cheaperinference.com/v1')
        ->and($config->model)->toBe('gpt-5.4-mini');
});

it('reads api key from environment', function () {
    putenv('CHEAPER_INFERENCE_API_KEY=env-key');

    $config = new CheaperInferenceConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('does not fall back to OPENAI_API_KEY', function () {
    $openAiApiKey = getenv('OPENAI_API_KEY');
    putenv('OPENAI_API_KEY=openai-key');

    try {
        $config = new CheaperInferenceConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new OpenAIChat($config))->toThrow(MissingParameterException::class);
    } finally {
        putenv($openAiApiKey === false ? 'OPENAI_API_KEY' : 'OPENAI_API_KEY='.$openAiApiKey);
    }
});

it('reads model from environment', function () {
    putenv('CHEAPER_INFERENCE_MODEL=custom-model');

    $config = new CheaperInferenceConfig('test-key');

    expect($config->model)->toBe('custom-model');
});

it('falls back to the default model when CHEAPER_INFERENCE_MODEL is set but empty', function () {
    putenv('CHEAPER_INFERENCE_MODEL=');

    $config = new CheaperInferenceConfig('test-key');

    expect($config->model)->toBe('gpt-5.4-mini');
});

it('allows overriding url, model and model options', function () {
    $config = new CheaperInferenceConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'custom-model',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('custom-model')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
