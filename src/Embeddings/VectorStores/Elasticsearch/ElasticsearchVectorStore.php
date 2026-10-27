<?php

namespace LLPhant\Embeddings\VectorStores\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Exception;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;

class ElasticsearchVectorStore extends VectorStoreBase
{
    final public const ES_INDEX = 'llphant';

    public bool $vectorDimSet = false;

    /**
     * @throws Exception
     */
    public function __construct(public Client $client, public string $indexName = self::ES_INDEX)
    {
        /** @var ElasticsearchResponse $existResponse */
        $existResponse = $client->indices()->exists(['index' => $indexName]);
        $existStatusCode = $existResponse->getStatusCode();

        if ($existStatusCode === 200) {
            $this->ensureMetadataMapping();

            return;
        }

        $mapping = [
            'index' => $indexName,
            'body' => [
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
                        // 'enabled' => false on an object field means: Elasticsearch/OpenSearch will store the field in source
                        // but will not parse/index any inner keys.
                        // metadata is kept as raw JSON (you can read it back).
                        'metadata' => [
                            'type' => 'object',
                            'enabled' => false,
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->create($mapping);
    }

    /**
     * @throws Exception
     */
    public function addDocument(Document $document): void
    {
        if ($document->embedding === null) {
            throw new Exception('document embedding must be set before adding a document');
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
     *
     * @throws Exception
     */
    public function addDocuments(array $documents, int $numberOfDocumentsPerRequest = 0): void
    {
        if ($documents === []) {
            return;
        }
        if ($documents[0]->embedding === null) {
            throw new Exception('document embedding must be set before adding a document');
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
        /** @var ElasticsearchResponse $bulkResponse */
        $bulkResponse = $this->client->bulk($params);
        $this->assertBulkSucceeded($bulkResponse->asArray());
        $this->client->indices()->refresh(['index' => $this->indexName]);
    }

    /**
     * {@inheritDoc}
     *
     * @param  array{filter?: string|array<string, mixed>, num_candidates?: int}  $additionalArguments
     *
     * num_candidates is used to tune approximate kNN for speed or accuracy (see : https://www.elastic.co/guide/en/elasticsearch/reference/current/knn-search.html#tune-approximate-knn-for-speed-accuracy)
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $numCandidates = max(50, $k * 4);
        if (array_key_exists('num_candidates', $additionalArguments)) {
            $numCandidates = $additionalArguments['num_candidates'];
        }
        $searchParams = [
            'index' => $this->indexName,
            'body' => [
                'knn' => [
                    'field' => 'embedding',
                    'query_vector' => $embedding,
                    'k' => $k,
                    'num_candidates' => $numCandidates,
                ],
                'sort' => [
                    '_score' => [
                        'order' => 'desc',
                    ],
                ],
            ],
        ];
        if (array_key_exists('filter', $additionalArguments)) {
            $searchParams['body']['knn']['filter'] = $additionalArguments['filter'];
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
        /** @var array{string: array{mappings: array{embedding: array{mapping: array{embedding: array{dims: int}}}}}} $response */
        $response = $this->client->indices()->getFieldMapping([
            'index' => $this->indexName,
            'fields' => 'embedding',
        ]);
        $mappings = $response[$this->indexName]['mappings'];
        if (
            array_key_exists('embedding', $mappings)
            && $mappings['embedding']['mapping']['embedding']['dims'] === $vectorDim
        ) {
            return;
        }

        $this->client->indices()->putMapping([
            'index' => $this->indexName,
            'body' => [
                'properties' => [
                    'embedding' => [
                        'type' => 'dense_vector',
                        'element_type' => 'float',
                        'dims' => $vectorDim,
                        'index' => true,
                        'similarity' => 'cosine',
                    ],
                ],
            ],
        ]);
        $this->vectorDimSet = true;
    }

    /**
     * @throws Exception
     */
    private function ensureMetadataMapping(): void
    {
        /** @var array<string, mixed> $response */
        $response = $this->client->indices()->getMapping(['index' => $this->indexName]);
        $metadataMapping = $response[$this->indexName]['mappings']['properties']['metadata'] ?? null;
        if (! is_array($metadataMapping)) {
            $this->client->indices()->putMapping([
                'index' => $this->indexName,
                'body' => [
                    'properties' => [
                        // enabled=false: keep metadata in _source, do not parse/index inner keys.
                        'metadata' => [
                            'type' => 'object',
                            'enabled' => false,
                        ],
                    ],
                ],
            ]);

            return;
        }

        $metadataType = $metadataMapping['type'] ?? null;
        $metadataEnabled = $metadataMapping['enabled'] ?? null;
        if ($metadataType === 'object' && $metadataEnabled === false) {
            return;
        }

        throw new Exception(sprintf(
            'Index "%s" has incompatible metadata mapping. Expected type=object with enabled=false, got: %s',
            $this->indexName,
            json_encode($metadataMapping, JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * @param  array{errors?: bool, items?: array<int, array<string, array{status?: int, _id?: string, error?: array{type?: string, reason?: string}}>>}  $bulkResponse
     *
     * @throws Exception
     */
    private function assertBulkSucceeded(array $bulkResponse): void
    {
        if (($bulkResponse['errors'] ?? false) !== true) {
            return;
        }

        $failures = [];
        foreach ($bulkResponse['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $operation = array_key_first($item);
            if ($operation === null || ! is_array($item[$operation])) {
                continue;
            }
            $operationResult = $item[$operation];
            $status = $operationResult['status'] ?? 0;
            if (! is_int($status) || $status < 300) {
                continue;
            }
            $error = $operationResult['error'] ?? [];
            $errorType = $error['type'] ?? 'unknown';
            $errorReason = $error['reason'] ?? 'unknown';
            $documentId = $operationResult['_id'] ?? 'unknown';
            $failures[] = sprintf(
                '%s/%s returned status %d (%s: %s)',
                $operation,
                $documentId,
                $status,
                $errorType,
                $errorReason,
            );
        }

        if ($failures === []) {
            throw new Exception('Elasticsearch bulk indexing failed with unknown item errors.');
        }

        throw new Exception(sprintf('Elasticsearch bulk indexing failed: %s', implode('; ', $failures)));
    }
}
