<?php

namespace LLPhant;

use GuzzleHttp\Client;

class DeepseekConfig
{
    public ?string $apiKey = null;

    public ?Client $client = null;

    public string $model = 'deepseek-chat';

    public int $maxTokens = 4096;

    public ?string $baseUrl = null;

    public ?int $timeout = null;

    /** @var array<string, mixed> */
    public array $modelOptions = [];
}
