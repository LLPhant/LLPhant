<?php

namespace LLPhant;

class GeminiOpenAIConfig extends OpenAIConfig
{
    public function __construct(?string $apiKey = null, string $url = 'https://generativelanguage.googleapis.com/v1beta/openai')
    {
        parent::__construct(
            // An empty key stops OpenAIConfig from falling back to OPENAI_API_KEY,
            // so an OpenAI key is never sent to Gemini.
            $apiKey ?? Utility::readEnvironment('GEMINI_API_KEY') ?? '',
            $url
        );
    }
}
