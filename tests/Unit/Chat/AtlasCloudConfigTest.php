<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\AtlasCloudConfig;

afterEach(function () {
    putenv('ATLASCLOUD_API_KEY');
    putenv('ATLASCLOUD_MODEL');
    unset(
        $_ENV['ATLASCLOUD_API_KEY'],
        $_ENV['ATLASCLOUD_MODEL'],
        $_SERVER['ATLASCLOUD_API_KEY'],
        $_SERVER['ATLASCLOUD_MODEL']
    );
});

it('uses atlas cloud defaults', function () {
    $config = new AtlasCloudConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://api.atlascloud.ai/v1')
        ->and($config->model)->toBe('qwen/qwen3.5-flash');
});

it('reads api key from environment', function () {
    putenv('ATLASCLOUD_API_KEY=env-key');

    $config = new AtlasCloudConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('reads model from environment', function () {
    putenv('ATLASCLOUD_MODEL=deepseek-ai/deepseek-v4-flash');

    $config = new AtlasCloudConfig('test-key');

    expect($config->model)->toBe('deepseek-ai/deepseek-v4-flash');
});

it('allows overriding url, model and model options', function () {
    $config = new AtlasCloudConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'qwen-turbo',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('qwen-turbo')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
