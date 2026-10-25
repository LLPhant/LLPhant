<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * Gisoo exposes an OpenAI-compatible API (chat, embeddings, images, audio).
 *
 * @see https://gisoo.pro/docs
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class GisooConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://gisoo.pro/api/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            // An empty key stops OpenAIConfig from falling back to OPENAI_API_KEY,
            // so an OpenAI key is never sent to Gisoo.
            apiKey: $apiKey ?? Utility::readEnvironment('GISOO_API_KEY') ?? '',
            url: $url,
            model: $model ?? Utility::readEnvironment('GISOO_MODEL', 'gpt-5-mini'),
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
