<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * Atlas Cloud exposes an OpenAI-compatible chat API.
 *
 * @see https://www.atlascloud.ai/docs/models/llm
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class AtlasCloudConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.atlascloud.ai/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            apiKey: $apiKey ?? Utility::readEnvironment('ATLASCLOUD_API_KEY'),
            url: $url,
            model: $model ?? 'owl',
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
