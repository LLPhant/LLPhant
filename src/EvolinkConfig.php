<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * Evolink exposes an OpenAI-compatible chat API.
 *
 * @see https://docs.evolink.ai
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class EvolinkConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://direct.evolink.ai/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            apiKey: $apiKey ?? Utility::readEnvironment('EVOLINK_API_KEY'),
            url: $url,
            model: $model ?? 'gpt-5.2',
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
