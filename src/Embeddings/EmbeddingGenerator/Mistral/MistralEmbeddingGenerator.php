<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\Mistral;

use Exception;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator;
use LLPhant\MistralAIConfig;

class MistralEmbeddingGenerator extends AbstractOpenAIEmbeddingGenerator
{
    /**
     * @throws Exception
     */
    public function __construct(MistralAIConfig $config = new MistralAIConfig())
    {
        parent::__construct($config);
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
