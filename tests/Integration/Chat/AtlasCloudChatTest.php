<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\AtlasCloudOpenAIConfig;
use LLPhant\Chat\OpenAIChat;

beforeEach(function () {
    if (getenv('ATLASCLOUD_API_KEY') === false || getenv('ATLASCLOUD_API_KEY') === '') {
        test()->markTestSkipped('ATLASCLOUD_API_KEY is not configured.');
    }

    if (getenv('RUN_ATLASCLOUD_INTEGRATION_TESTS') !== '1') {
        test()->markTestSkipped('Set RUN_ATLASCLOUD_INTEGRATION_TESTS=1 to run Atlas Cloud integration tests.');
    }
});

it('can generate some stuff', function () {
    $config = new AtlasCloudOpenAIConfig();
    $config->apiKey = getenv('ATLASCLOUD_API_KEY');
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff getting API key from env', function () {
    $chat = new OpenAIChat(new AtlasCloudOpenAIConfig());
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});
