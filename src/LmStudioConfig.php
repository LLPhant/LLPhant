<?php

    declare(strict_types=1);

    namespace LLPhant;

    class LmStudioConfig
    {
        /**
         * The name of the model to use. Must match a model loaded in LM Studio.
         * Examples: 'llama-2-7b-chat', 'mistral-7b-instruct', 'phi-2', etc.
         */
        public string $model = '';

        /**
         * The base URL of the LM Studio API, often /api/v0/ for endpoints
         * compatible with OpenAI (v1/chat/completions, v1/embeddings, etc.).
         */
        public string $url = 'http://localhost:1234/api/v0/';

        public bool $stream = false;
        public bool $formatJson = false;
        public ?float $timeout = null;
        public ?string $apiKey = null;

        /**
         * model options, example:
         * - temperature: Controls creativity (0.0 to 1.0)
         * - max_tokens: Maximum number of tokens to generate
         * - top_p: Nucleus sampling
         * - frequency_penalty: Repetition penalty
         * - presence_penalty: Presence penalty
         *
         * @see https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-completion
         *
         * @var array<string, mixed>
         */
        public array $modelOptions = [];
    }
