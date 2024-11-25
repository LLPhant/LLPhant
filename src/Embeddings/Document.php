<?php

declare(strict_types=1);

namespace LLPhant\Embeddings;

class Document
{
    public string $content;

    public ?string $formattedContent = null;

    /** @var float[]|null */
    public ?array $embedding = null;

    public string $sourceType = 'manual';

    public string $sourceName = 'manual';

    public string $hash = '';

    public int $chunkNumber = 0;

    /** @var array<string, mixed> */
    public array $metadata = []; // Extensible metadata

    /**
     * Add or update metadata fields.
     *
     * @param string $key
     * @param mixed $value
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Convert the document to a structured array, including metadata.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'formattedContent' => $this->formattedContent,
            'embedding' => $this->embedding,
            'sourceType' => $this->sourceType,
            'sourceName' => $this->sourceName,
            'hash' => $this->hash,
            'chunkNumber' => $this->chunkNumber,
            'metadata' => $this->metadata,
        ];
    }
}
