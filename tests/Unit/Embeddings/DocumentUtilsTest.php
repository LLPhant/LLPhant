<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings;

use LLPhant\Embeddings\DocumentUtils;

$documentData = [
    'content' => 'France',
    'formattedContent' => 'France',
    'embedding' => [0.1, 0.2],
    'sourceType' => 'manual',
    'sourceName' => 'manual',
    'hash' => 'a',
    'chunkNumber' => 0,
];

it('restores the metadata when creating a document from an array', function () use ($documentData) {
    $document = DocumentUtils::createDocumentFromArray([...$documentData, 'metadata' => ['itemId' => 42, 'tags' => ['geo']]]);

    expect($document->metadata)->toBe(['itemId' => 42, 'tags' => ['geo']]);
});

it('defaults to empty metadata when the array has none', function () use ($documentData) {
    $document = DocumentUtils::createDocumentFromArray($documentData);

    expect($document->metadata)->toBe([]);
});
