<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
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
        [
            'type' => 'vector',
            'path' => 'amenitiesVector',
            'numDimensions' => 3072,
            'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT,
        ],
        [
            'type' => 'filter',
            'path' => 'published',
        ],
    ]
)]
final class Listing extends DoctrineODMEmbeddingEntityBase
{
    public function __construct(
        #[ODM\Field(type: 'string')]
        public string $content,

        #[ODM\Field(type: 'collection')]
        public array $amenities = [],

        #[ODM\Field(type: 'collection')]
        public array $amenitiesVector = [],

        #[ODM\Field(type: 'bool')]
        public bool $published = true,
    ) {}
}
