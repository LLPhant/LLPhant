<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\OrcaRouterConfig;

afterEach(function () {
    putenv('ORCAROUTER_API_KEY');
    putenv('ORCAROUTER_MODEL');
    unset(
        $_ENV['ORCAROUTER_API_KEY'],
        $_ENV['ORCAROUTER_MODEL'],
        $_SERVER['ORCAROUTER_API_KEY'],
        $_SERVER['ORCAROUTER_MODEL']
    );
});

it('uses orcarouter defaults', function () {
    $config = new OrcaRouterConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://api.orcarouter.ai/v1')
        ->and($config->model)->toBe('openai/gpt-4o-mini');
});

it('reads api key from environment', function () {
    putenv('ORCAROUTER_API_KEY=env-key');

    $config = new OrcaRouterConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('reads model from environment', function () {
    putenv('ORCAROUTER_MODEL=deepseek/deepseek-v4-flash-0731');

    $config = new OrcaRouterConfig('test-key');

    expect($config->model)->toBe('deepseek/deepseek-v4-flash-0731');
});

it('falls back to the default model when ORCAROUTER_MODEL is set but empty', function () {
    putenv('ORCAROUTER_MODEL=');

    $config = new OrcaRouterConfig('test-key');

    expect($config->model)->toBe('openai/gpt-4o-mini');
});

it('allows overriding url, model and model options', function () {
    $config = new OrcaRouterConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'openai/gpt-4o',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('openai/gpt-4o')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
