<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\Mistral;

use Exception;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use OpenAI\Contracts\ClientContract;

use function getenv;

class MistralEmbeddingGenerator extends AbstractOpenAIEmbeddingGenerator
{
    /**
     * @throws Exception
     */
    public function __construct(?OpenAIConfig $config = null)
    {
        parent::__construct($config);

        if ($config instanceof OpenAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;

            return;
        }

        $apiKey = $config->apiKey ?? getenv('MISTRAL_API_KEY');
        if (! $apiKey) {
            throw new Exception('You have to provide a MISTRAL_API_KEY env var to request Mistral .');
        }

        $this->client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri('api.mistral.ai/v1')
            ->make();

        $this->uri = 'https://api.mistral.ai/v1/embeddings';
        $this->apiKey = $apiKey;
    }

    public function getEmbeddingLength(): int
    {
        return 1024;
    }

    public function getModelName(): string
    {
        return 'mistral-embed';
    }
}
