<?php

namespace LLPhant;

use OpenAI\Contracts\ClientContract;

abstract class AIConfig
{
    /**
     * @param  array<string,mixed>  $modelOptions
     */
    public function __construct(
        public ?string $apiKey = null,
        public ?string $url = null,
        public ?string $model = null,
        public ?ClientContract $client = null,
        public array $modelOptions = [],
    ) {
    }
}
