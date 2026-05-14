<?php

declare(strict_types=1);

use LLPhant\AtlasCloudOpenAIConfig;

it('uses Atlas Cloud defaults', function () {
    putenv('ATLASCLOUD_API_KEY');

    $config = new AtlasCloudOpenAIConfig();

    expect($config->url)->toBe('https://api.atlascloud.ai/v1')
        ->and($config->model)->toBe('deepseek-v3')
        ->and($config->apiKey)->toBeNull();
});

it('reads the Atlas Cloud API key from the environment', function () {
    putenv('ATLASCLOUD_API_KEY=test-atlas-key');

    $config = new AtlasCloudOpenAIConfig();

    expect($config->apiKey)->toBe('test-atlas-key');

    putenv('ATLASCLOUD_API_KEY');
});
