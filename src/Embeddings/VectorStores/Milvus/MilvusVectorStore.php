<?php

namespace LLPhant\Embeddings\VectorStores\Milvus;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentStore\DocumentStore;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use LLPhant\Exception\SecurityException;

class MilvusVectorStore extends VectorStoreBase implements DocumentStore
{
    final public const MILVUS_COLLECTION_NAME = 'llphant';

    public const OUTPUTFIELDS = ['id', 'content', 'formattedContent', 'sourceType', 'sourceName', 'hash', 'chunkNumber', 'embedding', 'metadata'];

    public bool $collectionExists = false;

    public function __construct(
        public MilvusClient $client,
        public string $collectionName = self::MILVUS_COLLECTION_NAME
    ) {
    }

    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents, int $numberOfDocumentsPerRequest = 0): void
    {
        if ($documents === []) {
            return;
        }
        $embeddingDimension = count((array) $documents[0]->embedding);
        $this->createCollectionIfNotExist($embeddingDimension);
        $response = $this->client->insertData(
            $this->collectionName,
            array_map(
                fn (Document $document): array => [
                    'embedding' => $document->embedding,
                    'content' => $document->content,
                    'formattedContent' => $document->formattedContent,
                    'sourceType' => $document->sourceType,
                    'sourceName' => $document->sourceName,
                    'hash' => $document->hash,
                    'chunkNumber' => $document->chunkNumber,
                    'metadata' => $document->metadata,
                ],
                $documents
            )
        );
        $this->checkResponseCode($response);
    }

    /**
     * {@inheritDoc}
     *
     * @param  array{filter?: string}  $additionalArguments
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        /** @var array{code: int, data: array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int, metadata?: array<string, mixed>}[]} $response */
        $response = $this->client->searchVector(
            $this->collectionName,
            $embedding,
            $k,
            array_key_exists('filter', $additionalArguments) ? $additionalArguments['filter'] : '',
            self::OUTPUTFIELDS
        );
        $this->checkResponseCode($response);

        return DocumentUtils::createDocumentsFromArray($response['data']);
    }

    private function createCollectionIfNotExist(int $dimension): void
    {
        if ($this->collectionExists) {
            return;
        }
        // It returns 200 if the collection exist AND is the same
        $response = $this->client->createCollection(
            collectionName: $this->collectionName,
            dimension: $dimension,
            metricType: 'COSINE',
            primaryField: 'id',
            vectorField: 'embedding'
        );
        $this->checkResponseCode($response);
        $this->collectionExists = true;
    }

    /**
     * @throws SecurityException
     */
    public function fetchDocumentsByChunkRange(string $sourceType, string $sourceName, int $leftIndex, int $rightIndex): iterable
    {
        $filters = \FILTER_SANITIZE_ENCODED;

        if ($sourceType !== \filter_var($sourceType, $filters)) {
            throw new SecurityException('Invalid source type');
        }

        if ($sourceName !== \filter_var($sourceName, $filters)) {
            throw new SecurityException('Invalid source name');
        }

        $response = $this->client->query(
            $this->collectionName,
            self::OUTPUTFIELDS,
            "sourceType == \"$sourceType\" and sourceName == \"$sourceName\" and ($leftIndex <= chunkNumber <= $rightIndex)",
        );
        $this->checkResponseCode($response);

        $documents = DocumentUtils::createDocumentsFromArray($response['data']);

        \usort($documents, fn (Document $d1, Document $d2): int => $d1->chunkNumber <=> $d2->chunkNumber);

        return $documents;
    }

    /**
     * @param  array<string, array<array<string, array<float>|int|string>>|int>  $response
     */
    protected function checkResponseCode(array $response): void
    {
        /** @var int $responseCode */
        $responseCode = $response['code'];
        if ($responseCode !== 200) {
            $msg = "Error while creating collection ($responseCode)";
            if (\array_key_exists('message', $response)) {
                /** @var string $responseMessage */
                $responseMessage = $response['message'];
                $msg .= ': '.$responseMessage;
            }
            throw new \Exception($msg);
        }
    }

    public function deleteCollection(): bool
    {
        if (! $this->collectionExists) {
            return false;
        }

        $response = $this->client->deleteCollection($this->collectionName);
        $this->checkResponseCode($response);

        return true;
    }
}
