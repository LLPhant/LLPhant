<?php

declare(strict_types=1);

namespace LLPhant;

use LLPhant\Chat\Enums\MistralAIChatModel;
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
        string $url = 'https://api.mistral.ai/v1',
        ?string $model = null,
        ?string $apiKey = null,
        ?ClientContract $client = null,
        array $modelOptions = []
    ) {
        $model ??= MistralAIChatModel::large->value;
        $apiKey ??= (getenv('MISTRAL_API_KEY') ?: null);
        parent::__construct($apiKey, $url, $model, $client, $modelOptions);
    }
}
