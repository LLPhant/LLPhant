<?php

namespace LLPhant;

class GeminiOpenAIConfig extends OpenAIConfig
{
    public string $url = 'https://generativelanguage.googleapis.com/v1beta/openai';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (getenv('GEMINI_API_KEY') ?: '');
    }
}
