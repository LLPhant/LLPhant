<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\VoyageAI;

final class Voyage3LiteEmbeddingGenerator extends AbstractVoyageAIEmbeddingGenerator
{
    public function getEmbeddingLength(): int
    {
        return 512;
    }

    public function getModelName(): string
    {
        return 'voyage-3-lite';
    }
}
