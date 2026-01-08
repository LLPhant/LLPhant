<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine\ODM;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use LLPhant\Embeddings\Document;

class DoctrineODMEmbeddingEntityBase extends Document
{
    #[ODM\Id(type: 'string', strategy: 'AUTO')]
    public mixed $id;

    #[ODM\Field(type: Type::COLLECTION)]
    public ?array $embedding = null;

    #[ODM\Field(type: Type::STRING)]
    public string $content;

    #[ODM\Field(type: Type::STRING)]
    public string $sourceType = 'manual';

    #[ODM\Field(type: Type::STRING)]
    public string $sourceName = 'manual';

    #[ODM\Field(type: Type::INT)]
    public int $chunkNumber = 0;

    public function getId(): string
    {
        return $this->id;
    }
}
