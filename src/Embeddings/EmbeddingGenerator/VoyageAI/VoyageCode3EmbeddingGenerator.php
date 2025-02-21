<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\VoyageAI;

final class VoyageCode3EmbeddingGenerator extends AbstractVoyageAIEmbeddingGenerator
{
    public function getEmbeddingLength(): int
    {
        return 1024;
    }

    public function getModelName(): string
    {
        return 'voyage-code-3';
    }
}
