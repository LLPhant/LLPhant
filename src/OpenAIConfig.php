<?php

declare(strict_types=1);

namespace LLPhant;

use LLPhant\Chat\Enums\OpenAIChatModel;
use OpenAI\Contracts\ClientContract;

/**
 * @see https://platform.openai.com/docs/api-reference/chat/create
 *
 * @phpstan-type ResponseFormat array{
 *     type: string,
 *     json_schema?: array<string, mixed>
 * }
 * @phpstan-type ModelOptions array<string,mixed>|array{
 *     frequency_penalty?: float|null,
 *     logit_bias?: array<string, mixed>|null,
 *     logprobs?: bool|null,
 *     top_logprobs?: int|null,
 *     max_tokens?: int|null,
 *     n?: int|null,
 *     presence_penalty?: float|null,
 *     response_format?: ResponseFormat|null,
 *     seed?: int|null,
 *     service_tier?: string|null,
 *     stop?: string|array<string>|null,
 *     temperature?: float|null,
 *     top_p?: float|null,
 *     user?: string|null,
 * }
 */
class OpenAIConfig extends AIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $url = null,
        ?string $model = null,
        ?ClientContract $client = null,
        array $modelOptions = [],
    ) {
        parent::__construct(
            apiKey: $apiKey ?? (getenv('OPENAI_API_KEY') ?: null),
            url: $url ?? (getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1'),
            model: $model ?? OpenAIChatModel::Gpt4Turbo->value,
            client: $client,
            modelOptions: $modelOptions,
        );
    }
}
