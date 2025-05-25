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
        if (str_starts_with(strtolower($platform::class), 'doctrine\dbal\platforms\mariadb')) {
            return new MariaDBVectorStoreType();
        }
        if (str_starts_with(strtolower($platform::class), 'doctrine\dbal\platforms\postgresql')) {
            return new PostgresqlVectorStoreType();
        }

        throw new \RuntimeException('Unsupported DoctrineVectorStore type: '.$platform::class);
    }
}
