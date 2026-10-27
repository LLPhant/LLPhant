<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * Heabsy exposes an OpenAI-compatible chat API.
 *
 * @see https://api.heabsy.com/docs
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class HeabsyConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.heabsy.com/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            // An empty key stops OpenAIConfig from falling back to OPENAI_API_KEY,
            // so an OpenAI key is never sent to Heabsy.
            apiKey: $apiKey ?? Utility::readEnvironment('HEABSY_API_KEY') ?? '',
            url: $url,
            model: $model ?? Utility::readEnvironment('HEABSY_MODEL', 'qwen38'),
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
