<?php

declare(strict_types=1);

namespace LLPhant;

/**
 * OllamaConfig for llmman (https://github.com/llmmanorg/llmman), which serves the Ollama API
 * on port 17434. The host can be overridden with LLMMAN_HOST ([host][:port]).
 */
class LlmmanConfig extends OllamaConfig
{
    public function __construct()
    {
        $host = Utility::readEnvironment('LLMMAN_HOST') ?? 'localhost:17434';
        if (str_starts_with($host, ':')) {
            $host = 'localhost'.$host;
        }
        if (! str_contains($host, ':')) {
            $host .= ':17434';
        }
        $this->url = 'http://'.$host.'/api/';
    }
}
