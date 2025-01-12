<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

class VectorUtils
{
    /**
     * @param  float[]  $vector
     */
    public static function getVectorAsString(array $vector, SupportedDoctrineVectorStore $vectorStore): string
    {
        if ($vector === []) {
            return '';
        }

        return match ($vectorStore) {
            SupportedDoctrineVectorStore::Postgres => '['.self::stringListOf($vector).']',
            SupportedDoctrineVectorStore::MariaDB => '['.self::stringListOf($vector).']',
        };
    }

    /**
     * @param  float[]  $vector
     */
    public static function stringListOf(array $vector): string
    {
        return \implode(',', $vector);
    }
}
