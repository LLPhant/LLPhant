<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class MistralAIConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.mistral.ai/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            apiKey: $apiKey ?? Utility::readEnvironment('MISTRAL_API_KEY'),
            url: $url,
            model: $model ?? 'mistral-small-latest',
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
