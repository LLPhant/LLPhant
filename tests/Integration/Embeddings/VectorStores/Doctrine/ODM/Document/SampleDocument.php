<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMEmbeddingEntityBase;

#[ODM\Document]
#[ODM\VectorSearchIndex(
    fields: [
        [
            'type' => 'vector',
            'path' => 'embedding',
            'numDimensions' => 3072,
            'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT,
        ],
    ]
)]
class SampleDocument extends DoctrineODMEmbeddingEntityBase
{
    #[ODM\Id]
    public mixed $id;

    public static function createDocument(string $type, string $name, string $content, int $chunkNumber): SampleDocument
    {
        $document = new SampleDocument();

        $document->sourceType = $type;
        $document->sourceName = $name;
        $document->content = $content;
        $document->chunkNumber = $chunkNumber;

        // Just fake data, we don't need this in tests
        $document->embedding = array_map(function () {
            return mt_rand() / mt_getrandmax();
        }, range(0, 1023));

        return $document;
    }
}
