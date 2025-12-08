<?php

namespace LLPhant\Embeddings\VectorStores\ChromaDB;

use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Client;
use Codewithkyrian\ChromaDB\Models\Collection;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;

class ChromaDBVectorStore extends VectorStoreBase
{
    private readonly Client $chromaDB;

    private Collection $currentCollection;

    public function __construct(
        string $host = 'localhost',
        int $port = 8000,
        string $database = 'default_database',
        string $tenant = 'default_tenant',
        ?string $authToken = null,
        string $collection = 'default_collection',
        private readonly int $apiBatchSize = 5
    ) {
        $factory = ChromaDB::factory()
            ->withHost($host)
            ->withPort($port)
            ->withDatabase($database)
            ->withTenant($tenant);

        if ($authToken !== null) {
            $factory->withAuthToken($authToken);
        }

        $this->chromaDB = $factory->connect();
        $this->setCurrentCollection($collection);
    }

    public function setCurrentCollection(string $collection): void
    {
        $this->currentCollection = $this->chromaDB->getOrCreateCollection($collection);
    }

    /**
     * {@inheritDoc}
     */
    public function addDocument(Document $document): void
    {
        $this->currentCollection->add(
            ids: [$this->getId($document)],
            embeddings: [$document->embedding ?? []],
            /**
             * @phpstan-ignore-next-line
             */
            metadatas: [$this->metadataFromDocument($document)],
            documents: [$document->content]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function addDocuments(array $documents): void
    {
        $chromaDBApiBuffer = new ChromaDBApiBuffer($this->currentCollection, $this->apiBatchSize);
        foreach ($documents as $document) {
            $chromaDBApiBuffer->add(
                $this->getId($document),
                $document->embedding,
                $this->metadataFromDocument($document),
                $document->content
            );
        }
        $chromaDBApiBuffer->executeCall();
    }

    /**
     * {@inheritDoc}
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $queryResult = $this->currentCollection
            ->query(queryEmbeddings: [$embedding], nResults: $k, include: ['metadatas', 'documents']);

        $result = [];

        if ($queryResult->documents !== null && $queryResult->metadatas !== null) {
            $itemsCount = is_countable($queryResult->documents[0]) ? \count($queryResult->documents[0]) : 0;

            for ($i = 0; $i < $itemsCount; $i++) {
                $newDocument = new Document();
                $newDocument->content = $queryResult->documents[0][$i];
                $metadata = $queryResult->metadatas[0][$i];
                $newDocument->hash = $metadata['hash'];
                $newDocument->sourceName = $metadata['sourceName'];
                $newDocument->sourceType = $metadata['sourceType'];
                $newDocument->chunkNumber = (int) $metadata['chunkNumber'];
                $result[] = $newDocument;
            }
        }

        return $result;
    }

    /**
     * @return array{hash: string, sourceName: string, sourceType: string, chunkNumber: int}
     */
    private function metadataFromDocument(Document $document): array
    {
        return
            [
                'hash' => $document->hash,
                'sourceName' => $document->sourceName,
                'sourceType' => $document->sourceType,
                'chunkNumber' => $document->chunkNumber,
            ];
    }

    private function getId(Document $document): string
    {
        return \hash('sha256', $document->content.DocumentUtils::getUniqueId($document));
    }
}
