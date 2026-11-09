<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * cheapaimodel exposes an OpenAI-compatible chat API.
 *
 * @see https://cheapaimodel.ai/docs
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class CheapaimodelConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.cheapaimodel.ai/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            // An empty key stops OpenAIConfig from falling back to OPENAI_API_KEY,
            // so an OpenAI key is never sent to cheapaimodel.
            apiKey: $apiKey ?? Utility::readEnvironment('CHEAPAIMODEL_API_KEY') ?? '',
            url: $url,
            model: $model ?? Utility::readEnvironment('CHEAPAIMODEL_MODEL', 'deepseek-v4-1-flash'),
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
