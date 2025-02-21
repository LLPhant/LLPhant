<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\VoyageAI;

final class VoyageCode2EmbeddingGenerator extends AbstractVoyageAIEmbeddingGenerator
{
    public function getEmbeddingLength(): int
    {
        return 1536;
    }

    public function getModelName(): string
    {
        return 'voyage-code-2';
    }
}
