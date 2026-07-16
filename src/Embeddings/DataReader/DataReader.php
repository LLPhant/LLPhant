<?php

namespace LLPhant\Embeddings\DataReader;

use LLPhant\Embeddings\Document;

interface DataReader
{
    /**
     * @return Document[]
     */
    public function getDocuments(): array;

    /**
     * Extract metadata from content.
     *
     * @return array<string, mixed>
     */
    public function extractMetadata(string $content): array;
}
