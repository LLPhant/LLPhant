<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\VectorStores\OpenSearch;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use OpenSearch\Client;
use RuntimeException;

class OpenSearchVectorStore extends VectorStoreBase
{
    final public const OS_INDEX = 'llphant';

    public bool $vectorDimSet = false;

    public function __construct(public Client $client, public string $indexName = self::OS_INDEX)
    {
        if ($client->indices()->exists(['index' => $indexName])) {
            return;
        }

        $mapping = [
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'index' => [
                        'knn' => true,
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'content' => [
                            'type' => 'text',
                        ],
                        'formattedContent' => [
                            'type' => 'text',
                        ],
                        'sourceType' => [
                            'type' => 'keyword',
                        ],
                        'sourceName' => [
                            'type' => 'keyword',
                        ],
                        'hash' => [
                            'type' => 'keyword',
                        ],
                        'chunkNumber' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ];

        $client->indices()->create($mapping);
    }

    public function addDocument(Document $document): void
    {
        if ($document->embedding === null) {
            throw new RuntimeException('Document embedding must be set before adding a document.');
        }

        $this->setVectorDimIfNotSet(count((array) $document->embedding));

        $this->client->index([
            'index' => $this->indexName,
            'body' => [
                'embedding' => $document->embedding,
                'content' => $document->content,
                'formattedContent' => $document->formattedContent ?? '',
                'sourceType' => $document->sourceType,
                'sourceName' => $document->sourceName,
                'hash' => $document->hash,
                'chunkNumber' => $document->chunkNumber,
                'metadata' => $document->metadata,
            ],
        ]);
        $this->client->indices()->refresh(['index' => $this->indexName]);
    }

    /**
     * @param  Document[]  $documents
     */
    public function addDocuments(array $documents, int $numberOfDocumentsPerRequest = 0): void
    {
        if ($documents === []) {
            return;
        }

        if ($documents[0]->embedding === null) {
            throw new RuntimeException('Document embedding must be set before adding a document.');
        }

        $this->setVectorDimIfNotSet(count((array) $documents[0]->embedding));

        $params = ['body' => []];

        foreach ($documents as $document) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                ],
            ];
            $params['body'][] = [
                'embedding' => $document->embedding,
                'content' => $document->content,
                'formattedContent' => $document->formattedContent ?? '',
                'sourceType' => $document->sourceType,
                'sourceName' => $document->sourceName,
                'hash' => $document->hash,
                'chunkNumber' => $document->chunkNumber,
                'metadata' => $document->metadata,
            ];
        }

        $this->client->bulk($params);
        $this->client->indices()->refresh(['index' => $this->indexName]);
    }

    /**
     * {@inheritDoc}
     *
     * @param  array{filter?: string|array<string, mixed>}  $additionalArguments
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $searchParams = [
            'index' => $this->indexName,
            'body' => [
                'query' => [
                    'knn' => [
                        'embedding' => [
                            'vector' => $embedding,
                            'k' => $k,
                        ],
                    ],
                ],
                'sort' => [
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ],
                ],
            ],
        ];

        if (array_key_exists('filter', $additionalArguments)) {
            $searchParams['body']['query']['knn']['embedding']['filter'] = $additionalArguments['filter'];
        }

        /** @var array{hits: array{hits: array{array{_source: array{embedding: float[], content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, chunkNumber: int, metadata?: array<string, mixed>}}}}} $rawResponse */
        $rawResponse = $this->client->search($searchParams);

        $documents = [];

        foreach ($rawResponse['hits']['hits'] as $hit) {
            $document = new Document();
            $document->embedding = $hit['_source']['embedding'];
            $document->content = $hit['_source']['content'];
            $document->formattedContent = $hit['_source']['formattedContent'];
            $document->sourceType = $hit['_source']['sourceType'];
            $document->sourceName = $hit['_source']['sourceName'];
            $document->hash = $hit['_source']['hash'];
            $document->chunkNumber = $hit['_source']['chunkNumber'];
            $document->metadata = $hit['_source']['metadata'] ?? [];
            $documents[] = $document;
        }

        return $documents;
    }

    private function setVectorDimIfNotSet(int $vectorDim): void
    {
        if ($this->vectorDimSet) {
            return;
        }

        /** @var array{string: array{mappings: array{embedding: array{mapping: array{embedding: array{dimension: int}}}}}} $response */
        $response = $this->client->indices()->getFieldMapping([
            'index' => $this->indexName,
            'fields' => 'embedding',
        ]);
        $mappings = $response[$this->indexName]['mappings'];

        if (
            array_key_exists('embedding', $mappings)
            && $mappings['embedding']['mapping']['embedding']['dimension'] === $vectorDim
        ) {
            return;
        }

        $this->client->indices()->putMapping([
            'index' => $this->indexName,
            'body' => [
                'properties' => [
                    'embedding' => [
                        'type' => 'knn_vector',
                        'dimension' => $vectorDim,
                        'index' => true,
                        'similarity' => 'cosine',
                        'method' => [
                            'name' => 'hnsw',
                            'engine' => 'lucene',
                        ],
                    ],
                ],
            ],
        ]);

        $this->vectorDimSet = true;
    }
}
