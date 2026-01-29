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
    ]
)]
#[VectorSearchIndex(
    fields: [
        [
            'type' => 'vector',
            'path' => 'anotherEmbedding',
            'numDimensions' => 3072,
            'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT,
        ],
    ],
    name: 'another_index',
)]
class MultipleVectorIndexesDocument extends DoctrineODMEmbeddingEntityBase
{
    public function __construct(
        #[ODM\Field(type: 'collection')]
        public array $anotherEmbedding,
    ) {
    }
}
