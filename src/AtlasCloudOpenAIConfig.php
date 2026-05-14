<?php

namespace LLPhant;

/**
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class AtlasCloudOpenAIConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        ?string $apiKey = null,
        string $url = 'https://api.atlascloud.ai/v1',
        ?string $model = 'deepseek-v3',
        array $modelOptions = [],
    ) {
        parent::__construct(
            $apiKey ?? Utility::readEnvironment('ATLASCLOUD_API_KEY'),
            $url,
            $model,
            modelOptions: $modelOptions,
        );
    }
}
