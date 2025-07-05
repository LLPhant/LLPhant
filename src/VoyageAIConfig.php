<?php

declare(strict_types=1);

namespace LLPhant;

/**
 * @phpstan-type VoyageModelOptions array<string,mixed>|array{
 *  input_type: string,
 *  output_dtype: string,
 *  output_dimension: int|null,
 *  truncation: boolean,
 *  encoding_format: string|null
 * }
 */
class VoyageAIConfig extends OpenAIConfig
{
    /**
     * @see https://docs.voyageai.com/reference/embeddings-api
     *
     * @param  VoyageModelOptions  $modelOptions
     */
    public function __construct(?string $apiKey = null, string $url = 'https://api.voyageai.com/v1', array $modelOptions = [])
    {
        parent::__construct($apiKey ?? (getenv('VOYAGE_AI_API_KEY') ?: null), $url, modelOptions: $modelOptions);
    }
}
