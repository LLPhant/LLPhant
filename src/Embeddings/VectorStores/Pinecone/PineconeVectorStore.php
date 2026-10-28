<?php

namespace LLPhant\Embeddings\VectorStores\Pinecone;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentStore\DocumentStore;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use LLPhant\Exception\SecurityException;

class PineconeVectorStore extends VectorStoreBase implements DocumentStore
{
    public const DEFAULT_NAMESPACE = '';

    public function __construct(
        public PineconeClient $client,
        public string $namespace = self::DEFAULT_NAMESPACE,
        public int $dimension = 0
    ) {
    }

    /**
     * Creates an index in Pinecone and returns the index host URL for data-plane operations.
     * Safe to call when the index already exists (idempotent).
     *
     * @param  string  $name  Index name
     * @param  int  $dimension  Vector dimension (must match the embedding generator)
     * @param  string  $metric  Similarity metric ('cosine', 'euclidean', 'dotproduct')
     * @param  string|null  $controlHost  Override for the control-plane base URL (see PineconeClient::createIndex)
     * @return string The index host URL to use for data-plane operations
     */
    public function createIndex(
        string $name,
        int $dimension,
        string $metric = 'cosine',
        ?string $controlHost = null
    ): string {
        return $this->client->createIndex($name, $dimension, $metric, $controlHost);
    }

    /**
     * Deletes a Pinecone index. Safe to call when the index does not exist (idempotent).
     *
     * @param  string  $name  Index name
     * @param  string|null  $controlHost  Override for the control-plane base URL (see PineconeClient::deleteIndex)
     */
    public function deleteIndex(string $name, ?string $controlHost = null): void
    {
        $this->client->deleteIndex($name, $controlHost);
    }

    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    /**
     * @param  Document[]  $documents
     */
    public function addDocuments(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $vectors = array_map(
            function (Document $document): array {
                $metadata = [
                    'content' => $document->content,
                    'formattedContent' => $document->formattedContent ?? $document->content,
                    'sourceType' => $document->sourceType,
                    'sourceName' => $document->sourceName,
                    'hash' => $document->hash,
                    'chunkNumber' => $document->chunkNumber,
                ];

                // Pinecone metadata only accepts flat values, so the free-form array is kept as a JSON string.
                if ($document->metadata !== []) {
                    $metadata['metadata'] = json_encode($document->metadata, JSON_THROW_ON_ERROR);
                }

                return [
                    'id' => $document->hash !== '' ? $document->hash : hash('sha256', $document->formattedContent ?? $document->content),
                    'values' => $document->embedding,
                    'metadata' => $metadata,
                ];
            },
            $documents
        );

        $success = $this->client->upsert($vectors, $this->namespace);

        if (! $success) {
            throw new \Exception('Failed to upsert vectors to Pinecone.');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param  array{filter?: array<string, mixed>}  $additionalArguments
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $filter = $additionalArguments['filter'] ?? [];

        $matches = $this->client->query(
            vector: $embedding,
            topK: $k,
            filter: $filter,
            namespace: $this->namespace
        );

        return $this->mapMatchesToDocuments($matches);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchDocumentsByChunkRange(string $sourceType, string $sourceName, int $leftIndex, int $rightIndex): iterable
    {
        if (trim($sourceType) === '' || trim($sourceName) === '') {
            throw new SecurityException('Invalid source type or name');
        }

        if ($this->dimension > 0) {
            $dimension = $this->dimension;
        } else {
            $stats = $this->client->describeIndexStats();
            $dimension = $stats['dimension'] ?? 1536;
        }
        $zeroVector = array_fill(0, $dimension, 0.0);

        $filter = [
            'sourceType' => ['$eq' => $sourceType],
            'sourceName' => ['$eq' => $sourceName],
            'chunkNumber' => [
                '$gte' => $leftIndex,
                '$lte' => $rightIndex,
            ],
        ];
        $limit = $rightIndex - $leftIndex + 1;
        $matches = $this->client->query(
            vector: $zeroVector,
            topK: $limit,
            filter: $filter,
            namespace: $this->namespace
        );
        $documents = $this->mapMatchesToDocuments($matches);

        usort($documents, fn (Document $a, Document $b) => $a->chunkNumber <=> $b->chunkNumber);

        return $documents;
    }

    /**
     * @param  array<mixed>  $matches
     * @return Document[]
     */
    protected function mapMatchesToDocuments(array $matches): array
    {
        $documents = [];
        foreach ($matches as $match) {
            $metadata = $match['metadata'] ?? [];

            $doc = new Document();
            $doc->content = $metadata['content'] ?? '';
            $doc->formattedContent = $metadata['formattedContent'] ?? '';
            $doc->sourceType = $metadata['sourceType'] ?? '';
            $doc->sourceName = $metadata['sourceName'] ?? '';
            $doc->hash = $match['id'];
            $doc->chunkNumber = isset($metadata['chunkNumber']) ? (int) $metadata['chunkNumber'] : 0;

            if (isset($metadata['metadata']) && is_string($metadata['metadata'])) {
                $decoded = json_decode($metadata['metadata'], true);
                $doc->metadata = is_array($decoded) ? $decoded : [];
            }

            $documents[] = $doc;
        }

        return $documents;
    }
}
