<?php

namespace Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\VectorSearchIndex;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMEmbeddingEntityBase;

#[Document]
#[VectorSearchIndex(
    fields: [
        [
            'type' => 'filter',
            'path' => 'published',
        ],
    ]
)]
class DocumentWithoutVectorField extends DoctrineODMEmbeddingEntityBase
{
    public function __construct(
        #[ODM\Field(type: 'boolean')]
        public bool $published = true
    ) {
    }
}
