<?php

declare(strict_types=1);

namespace LLPhant;

use Gemini\Contracts\ClientContract;

/**
 * Configuration for the Gemini API.
 */
class GeminiConfig
{
    /** @var string $apiKey Gemini API token */
    public string $apiKey;

    /** @var string $url Gemini API URL */
    public string $url;

    /** @var ClientContract|null $client Client to communicate with the Gemini API */
    public ?ClientContract $client = null;

    /** @var string $model Model to use for the Gemini API */
    public string $model;

    /**
     * model options, example:
     * - temperature
     * - maxOutputTokens
     * - candidateCount
     * - stopSequences
     * - topP
     * - topK
     *
     * @see https://ai.google.dev/gemini-api/docs/models/generative-models#model-parameters
     *
     * @var array<string, mixed> $modelOptions
     */
    public array $modelOptions = [];
}
