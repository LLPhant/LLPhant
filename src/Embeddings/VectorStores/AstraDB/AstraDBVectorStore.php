<?php

namespace LLPhant\Embeddings\VectorStores\AstraDB;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;

class AstraDBVectorStore extends VectorStoreBase
{
    private ?int $embeddingLength = null;

    public function __construct(private readonly AstraDBClient $client = new AstraDBClient())
    {
    }

    public function getEmbeddingLength(): int
    {
        if ($this->embeddingLength === null) {
            $this->embeddingLength = $this->client->collectionVectorDimension();
        }

        return $this->embeddingLength;
    }

    public function createCollection(int $embeddingLength): void
    {
        $this->client->createCollection($embeddingLength);
        $this->embeddingLength = $embeddingLength;
    }

    public function deleteCollection(): void
    {
        $this->client->deleteCollection();
        $this->embeddingLength = null;
    }

    public function cleanCollection(): void
    {
        $this->client->cleanCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    /**
     * {@inheritDoc}
     */
    public function addDocuments(array $documents): void
    {
        $this->client->insertData(
            array_map(
                fn (Document $document): array => [
                    '_id' => $this->getId($document),
                    '$vector' => $document->embedding,
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
    }

    /**
     * {@inheritDoc}
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $rawResult = $this->client->similaritySearch($embedding, $k);

        return DocumentUtils::createDocumentsFromArray($rawResult);
    }

    /**
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollectionIfDoesNotExist(int $embeddingLength, string $metricType = 'cosine'): void
    {
        if ($this->client->collectionVectorDimension() === 0) {
            $this->client->createCollection($embeddingLength, $metricType);
        }
    }

    private function getId(Document $document): string
    {
        return \hash('sha256', $document->content.DocumentUtils::getUniqueId($document));
    }
}
