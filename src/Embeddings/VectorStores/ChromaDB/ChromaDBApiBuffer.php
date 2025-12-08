<?php

namespace LLPhant\Embeddings\VectorStores\ChromaDB;

use Codewithkyrian\ChromaDB\Models\Collection;

class ChromaDBApiBuffer
{
    /**
     * @var string[]
     */
    private array $ids = [];

    /**
     * @var float[][]
     */
    private array $embeddings = [];

    /**
     * @var array<array<string, int|string>>
     */
    private array $metadata = [];

    /**
     * @var string[]
     */
    private array $contents = [];

    public function __construct(private readonly Collection $currentCollection, private readonly int $batchSize = 5)
    {
    }

    /**
     * @param  float[]|null  $embedding
     * @param  array<string, int|string>  $metadata
     */
    public function add(string $id, ?array $embedding, array $metadata, string $content): void
    {
        $this->ids[] = $id;
        if ($embedding !== null) {
            $this->embeddings[] = $embedding;
        }
        $this->metadata[] = $metadata;
        $this->contents[] = $content;

        if (\count($this->ids) >= $this->batchSize) {
            $this->executeCall();
        }
    }

    public function executeCall(): void
    {
        $this->currentCollection->add(
            $this->ids,
            $this->embeddings === [] ? null : $this->embeddings,
            $this->metadata,
            $this->contents
        );
        $this->ids = [];
        $this->embeddings = [];
        $this->metadata = [];
        $this->contents = [];
    }
}
