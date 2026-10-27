<?php

namespace Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\VectorSearchIndex;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMEmbeddingEntityBase;

#[Document]
#[VectorSearchIndex(
    fields: [
        [
            'type' => 'vector',
            'path' => 'embedding',
            'numDimensions' => 3072,
            'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT,
        ],
        [
            'type' => 'filter',
            'path' => 'published',
        ],
    ]
)]
class SingleVectorFieldDocument extends DoctrineODMEmbeddingEntityBase
{
    public function __construct(
        #[ODM\Field(type: 'boolean')]
        public bool $published = true
    ) {
    }
}
