<?php

declare(strict_types=1);

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

/**
 * OrcaRouter exposes an OpenAI-compatible chat API.
 *
 * @see https://www.orcarouter.ai
 *
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class OrcaRouterConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.orcarouter.ai/v1',
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            apiKey: $apiKey ?? Utility::readEnvironment('ORCAROUTER_API_KEY'),
            url: $url,
            model: $model ?? Utility::readEnvironment('ORCAROUTER_MODEL', 'openai/gpt-4o-mini'),
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
