<?php

namespace LLPhant;

class GeminiOpenAIConfig extends OpenAIConfig
{
    public function __construct(?string $apiKey = null, string $url = 'https://generativelanguage.googleapis.com/v1beta/openai')
    {
        parent::__construct(
            $apiKey ?? Utility::readEnvironment('GEMINI_API_KEY'),
            $url
        );
    }
}
