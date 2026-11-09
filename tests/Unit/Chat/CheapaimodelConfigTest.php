<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\CheapaimodelConfig;
use LLPhant\Exception\MissingParameterException;

afterEach(function () {
    putenv('CHEAPAIMODEL_API_KEY');
    putenv('CHEAPAIMODEL_MODEL');
    unset(
        $_ENV['CHEAPAIMODEL_API_KEY'],
        $_ENV['CHEAPAIMODEL_MODEL'],
        $_SERVER['CHEAPAIMODEL_API_KEY'],
        $_SERVER['CHEAPAIMODEL_MODEL']
    );
});

it('uses cheapaimodel defaults', function () {
    $config = new CheapaimodelConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://api.cheapaimodel.ai/v1')
        ->and($config->model)->toBe('deepseek-v4-1-flash');
});

it('reads api key from environment', function () {
    putenv('CHEAPAIMODEL_API_KEY=env-key');

    $config = new CheapaimodelConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('does not fall back to OPENAI_API_KEY', function () {
    $openAiApiKey = getenv('OPENAI_API_KEY');
    putenv('OPENAI_API_KEY=openai-key');

    try {
        $config = new CheapaimodelConfig();

        expect($config->apiKey)->toBe('')
            ->and(fn () => new OpenAIChat($config))->toThrow(MissingParameterException::class);
    } finally {
        putenv($openAiApiKey === false ? 'OPENAI_API_KEY' : 'OPENAI_API_KEY='.$openAiApiKey);
    }
});

it('reads model from environment', function () {
    putenv('CHEAPAIMODEL_MODEL=custom-model');

    $config = new CheapaimodelConfig('test-key');

    expect($config->model)->toBe('custom-model');
});

it('falls back to the default model when CHEAPAIMODEL_MODEL is set but empty', function () {
    putenv('CHEAPAIMODEL_MODEL=');

    $config = new CheapaimodelConfig('test-key');

    expect($config->model)->toBe('deepseek-v4-1-flash');
});

it('allows overriding url, model and model options', function () {
    $config = new CheapaimodelConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'custom-model',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('custom-model')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
