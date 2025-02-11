<?php

declare(strict_types=1);

namespace LLPhant\Embeddings;

class Document
{
    public mixed $id;

    public string $content;

    public ?string $formattedContent = null;

    /** @var float[]|null */
    public ?array $embedding = null;

    public string $sourceType = 'manual';

    public string $sourceName = 'manual';

    public string $hash = '';

    public int $chunkNumber = 0;
}
