<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine\ODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentStore\DocumentStore;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type SearchIndexMapping from ClassMetadata
 * @phpstan-import-type VectorSearchIndexDefinition from ClassMetadata
 */
final class DoctrineODMVectorStore extends VectorStoreBase implements DocumentStore
{
    /**
     * @param  class-string<DoctrineODMEmbeddingEntityBase>  $documentClassName
     */
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly string $documentClassName,
        private readonly string $vectorSearchIndex = 'default',
    ) {
    }

    /**
     * @throws MongoDBException
     * @throws Throwable
     */
    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    /**
     * @throws Throwable
     * @throws MongoDBException
     */
    public function addDocuments(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        foreach ($documents as $document) {
            $this->documentManager->persist($document);
        }

        $this->documentManager->flush();
    }

    /**
     * @param  array{'path'?: string, 'filter'?: array<string, mixed>}  $additionalArguments
     * @return DoctrineEmbeddingEntityBase[]
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): iterable
    {
        [$index, $path] = $this->extractMetadata($additionalArguments['path'] ?? null);

        $this->ensureSearchIndexExists();

        $builder = $this->documentManager->createAggregationBuilder($this->documentClassName)
            ->hydrate($this->documentClassName)
            ->vectorSearch()
            ->index($index)
            ->path($path)
            ->queryVector($embedding)
            ->limit($k)
            ->numCandidates($k * 10);

        if (isset($additionalArguments['filter'])) {
            $builder->filter($additionalArguments['filter']);
        }

        $agg = $builder->getAggregation();

        return $agg->execute()->toArray();
    }

    public function fetchDocumentsByChunkRange(string $sourceType, string $sourceName, int $leftIndex, int $rightIndex): iterable
    {
        /** @var DocumentRepository<DoctrineODMEmbeddingEntityBase> $repository */
        $repository = $this->documentManager->getRepository($this->documentClassName);

        $query = $repository->createQueryBuilder()
            ->field('sourceType')->equals($sourceType)
            ->field('sourceName')->equals($sourceName)
            ->field('chunkNumber')->gte($leftIndex)
            ->field('chunkNumber')->lte($rightIndex)
            ->getQuery();

        return $query->toArray();
    }

    /**
     * @throws MongoDBException
     */
    private function ensureSearchIndexExists(): void
    {
        $schemaManager = $this->documentManager->getSchemaManager();

        $schemaManager->createDocumentSearchIndexes($this->documentClassName);
        $schemaManager->waitForSearchIndexes([$this->documentClassName]);
    }

    /**
     * @param  string|null  $path  Vector path to use in case multiple vector fields are defined
     * @return string[]
     *
     * @throws RuntimeException
     */
    private function extractMetadata(?string $path): array
    {
        $indexes = $this->documentManager->getClassMetadata($this->documentClassName)->getSearchIndexes();

        /** @var SearchIndexMapping $index */
        $index = $this->resolveVectorSearchIndex($indexes);

        /** @var VectorSearchIndexDefinition $definition */
        $definition = $index['definition'];

        $vectorFields = array_filter($definition['fields'], function (array $field) use ($path): bool {
            if ($field['type'] !== 'vector') {
                return false;
            }

            return $path === null || $field['path'] === $path;
        });

        if (count($vectorFields) > 1) {
            throw new RuntimeException(sprintf(
                'Multiple vector fields found on document class %s. You must specify the "path" in additionalArguments.',
                $this->documentClassName
            ));
        }

        if (empty($vectorFields)) {
            throw new RuntimeException(sprintf(
                'No vector field found on document class %s for index name %s.',
                $this->documentClassName,
                $this->vectorSearchIndex,
            ));
        }

        return [$index['name'], array_shift($vectorFields)['path']];
    }

    /**
     * @param  list<SearchIndexMapping>  $indexes
     * @return SearchIndexMapping
     */
    private function resolveVectorSearchIndex(array $indexes): array
    {
        if (empty($indexes)) {
            throw new RuntimeException(sprintf(
                'No VectorSearchIndex attribute found on document class %s.',
                $this->documentClassName
            ));
        }

        $indexes = array_filter(
            $indexes,
            fn (array $index): bool => $index['name'] === $this->vectorSearchIndex && $index['type'] === 'vectorSearch'
        );

        if (empty($indexes)) {
            throw new RuntimeException(sprintf(
                'No VectorSearchIndex attribute found on document class %s for index name %s.',
                $this->documentClassName,
                $this->vectorSearchIndex,
            ));
        }

        return array_shift($indexes);
    }
}
