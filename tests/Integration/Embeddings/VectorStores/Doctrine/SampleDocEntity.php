<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\VectorStores\Doctrine;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase;

#[Entity]
#[Table(name: 'test_doc')]
class SampleDocEntity extends DoctrineEmbeddingEntityBase
{
    public static function createDocument(string $type, string $name, string $content, int $chunkNumber): SampleDocEntity
    {
        $document = new SampleDocEntity();
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
