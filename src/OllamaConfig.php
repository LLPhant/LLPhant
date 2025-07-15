<?php

declare(strict_types=1);

namespace LLPhant;

class OllamaConfig
{
    public string $model;

    public string $url = 'http://localhost:11434/api/';

    public bool $stream = false;

    public bool $formatJson = false;

    public ?int $timeout = null;

    public ?string $apiKey = null;

    /**
     * model options, example:
     * - options
     * - template
     * - raw
     * - keep_alive
     *
     * @see https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-completion
     *
     * @var array<string, mixed>
     */
    public array $modelOptions = [];
}
