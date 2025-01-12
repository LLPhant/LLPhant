<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;

enum SupportedDoctrineVectorStore: string
{
    case Postgres = 'postgresql';
    case MariaDB = 'mysql';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return \array_map(fn (SupportedDoctrineVectorStore $v): string => $v->value, self::cases());
    }

    public static function fromPlatform(AbstractPlatform $platform): self
    {
        return self::from($platform->getName());
    }
}
