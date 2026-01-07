<?php

namespace LLPhant\Embeddings\VectorStores\MongoDB;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use MongoDB\Builder\Pipeline;
use MongoDB\Builder\Stage;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use MongoDB\Model\BSONDocument;

class MongoDBVectorStore extends VectorStoreBase
{
    final public const MONGODB_EMBEDDING_FIELD = 'embedding';

    private readonly Collection $collection;

    public function __construct(
        Client $client,
        string $database,
        string $collection = 'llphant',
        private readonly string $vectorSearchIndex = 'llphant_vector_search_index',
    ) {
        $this->collection = $client->selectCollection($database, $collection);
    }

    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents): void
    {
        $this->collection->insertMany(
            array_map(
                fn (Document $document): array => [
                    '_id' => $this->getId($document),
                    self::MONGODB_EMBEDDING_FIELD => $document->embedding,
                    'content' => $document->content,
                    'formattedContent' => $document->formattedContent,
                    'sourceType' => $document->sourceType,
                    'sourceName' => $document->sourceName,
                    'hash' => $document->hash,
                    'chunkNumber' => $document->chunkNumber,
                ],
                $documents
            ),
            ['writeConcern' => new WriteConcern(WriteConcern::MAJORITY)]
        );
    }

    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): iterable
    {
        $vectorDimension = count($embedding);
        $this->ensureSearchIndexExists($vectorDimension);

        /** @var int $numCandidates */
        $numCandidates = $additionalArguments['numCandidates'] ?? max(100, $k * 10);

        /** @var array{string: mixed} $filter */
        $filter = $additionalArguments['filter'] ?? [];

        // Build a MongoDB Atlas Vector Search pipeline
        // Docs: https://www.mongodb.com/docs/php-library/current/vector-search/
        $pipeline = new Pipeline(
            Stage::vectorSearch(
                index: $this->vectorSearchIndex,
                limit: $k,
                path: self::MONGODB_EMBEDDING_FIELD,
                queryVector: $embedding,
                filter: $filter,
                numCandidates: $numCandidates,
            ),
            Stage::project(
                _id: 0,
            ),
        );

        $data = $this->collection->aggregate(
            $pipeline,
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        )->toArray();

        return DocumentUtils::createDocumentsFromArray($data);
    }

    private function ensureSearchIndexExists(int $vectorDimension): void
    {
        $indexes = array_filter(
            [...$this->collection->listSearchIndexes()],
            fn (BSONDocument $index): bool => $index->offsetGet('name') === $this->vectorSearchIndex
        );

        if ($this->isSearchIndexReady($indexes[0] ?? null)) {
            return;
        }

        if (empty($indexes)) {
            $this->collection->createSearchIndex(
                [
                    'fields' => [[
                        'type' => 'vector',
                        'path' => self::MONGODB_EMBEDDING_FIELD,
                        'numDimensions' => $vectorDimension,
                        'similarity' => 'dotProduct',
                    ]],
                ],
                ['name' => $this->vectorSearchIndex, 'type' => 'vectorSearch'],
            );
        }

        $this->ensureSearchIndexExists($vectorDimension);
    }

    private function isSearchIndexReady(?BSONDocument $index): bool
    {
        if (! $index instanceof BSONDocument) {
            return false;
        }

        if ($index->offsetGet('status') === 'FAILED') {
            throw new \RuntimeException('MongoDB Vector Search index creation failed.');
        }

        if ($index->offsetGet('status') !== 'READY') {
            usleep(1000);

            return false;
        }

        return true;
    }

    private function getId(Document $document): string
    {
        return \hash('sha256', $document->content.DocumentUtils::getUniqueId($document));
    }
}
