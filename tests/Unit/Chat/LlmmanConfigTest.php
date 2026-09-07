<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use LLPhant\Chat\OllamaChat;
use LLPhant\LlmmanConfig;

afterEach(function () {
    putenv('LLMMAN_HOST');
    unset($_ENV['LLMMAN_HOST'], $_SERVER['LLMMAN_HOST']);
});

it('uses llmman defaults', function () {
    $config = new LlmmanConfig();

    expect($config->url)->toBe('http://localhost:17434/api/');
});

it('reads host and port from LLMMAN_HOST', function () {
    putenv('LLMMAN_HOST=llmman.local:8080');

    expect((new LlmmanConfig())->url)->toBe('http://llmman.local:8080/api/');
});

it('completes a partial LLMMAN_HOST', function () {
    putenv('LLMMAN_HOST=llmman.local');
    expect((new LlmmanConfig())->url)->toBe('http://llmman.local:17434/api/');

    putenv('LLMMAN_HOST=:8080');
    expect((new LlmmanConfig())->url)->toBe('http://localhost:8080/api/');
});

it('can be used with OllamaChat', function () {
    $config = new LlmmanConfig();
    $config->model = 'gemma4';
    $chat = new OllamaChat($config);

    expect((string) $chat->client->getConfig('base_uri'))->toBe('http://localhost:17434/api/');
});
