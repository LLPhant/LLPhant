<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\EvolinkConfig;

afterEach(function () {
    putenv('EVOLINK_API_KEY');
    unset($_ENV['EVOLINK_API_KEY'], $_SERVER['EVOLINK_API_KEY']);
});

it('uses evolink defaults', function () {
    $config = new EvolinkConfig('test-key');

    expect($config->apiKey)->toBe('test-key')
        ->and($config->url)->toBe('https://direct.evolink.ai/v1')
        ->and($config->model)->toBe('gpt-5.2');
});

it('reads api key from environment', function () {
    putenv('EVOLINK_API_KEY=env-key');

    $config = new EvolinkConfig();

    expect($config->apiKey)->toBe('env-key');
});

it('allows overriding url, model and model options', function () {
    $config = new EvolinkConfig(
        apiKey: 'test-key',
        url: 'https://custom.example/v1',
        model: 'gemini-2.5-flash',
        modelOptions: ['temperature' => 0.2]
    );

    expect($config->url)->toBe('https://custom.example/v1')
        ->and($config->model)->toBe('gemini-2.5-flash')
        ->and($config->modelOptions)->toBe(['temperature' => 0.2]);
});
