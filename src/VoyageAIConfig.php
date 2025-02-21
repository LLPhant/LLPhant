<?php

declare(strict_types=1);

namespace LLPhant;

class VoyageAIConfig extends OpenAIConfig
{
    public string $url = 'https://api.voyageai.com/v1';

    /**
     * model options, example:
     * - input_type
     * - output_dtype
     * - output_dimension
     * - truncation
     * - encoding_format
     *
     * @see https://docs.voyageai.com/reference/embeddings-api
     *
     * @var array<string, mixed>
     */
    public array $modelOptions = [];
}
