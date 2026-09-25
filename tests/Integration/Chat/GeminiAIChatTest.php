<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\GeminiOpenAIConfig;

it('can generate some stuff', function () {
    $config = new GeminiOpenAIConfig();
    $config->apiKey = getenv('GEMINI_API_KEY');
    $config->model = 'gemini-2.0-flash';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff getting API key from env', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-2.0-flash';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});
