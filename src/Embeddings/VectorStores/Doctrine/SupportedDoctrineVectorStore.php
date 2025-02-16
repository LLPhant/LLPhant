<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;

abstract class SupportedDoctrineVectorStore
{
    /**
     * @param  float[]  $vector
     */
    abstract public function getVectorAsString(array $vector): string;

    abstract public function convertToDatabaseValueSQL(string $sqlExpr): string;

    abstract public function addCustomisationsTo(EntityManagerInterface $entityManager): void;

    abstract public function l2DistanceName(): string;

    /**
     * @param  float[]  $vector
     */
    protected function stringListOf(array $vector): string
    {
        return \implode(',', $vector);
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'postgresql',
            'mysql',
        ];
    }

    public static function fromPlatform(AbstractPlatform $platform): self
    {
        return match ($platform->getName()) {
            'postgresql' => new PostgresqlVectorStoreType(),
            'mysql' => new MariaDBVectorStoreType(),
            default => throw new \RuntimeException('Unsupported DoctrineVectorStore type'),
        };
    }
}
