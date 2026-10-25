<?php

declare(strict_types=1);

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\OpenSearch\OpenSearchVectorStore;
use OpenSearch\ClientBuilder;

it('tests a full embedding flow with OpenSearch', function () {
    // Get the already embedded france.txt and paris.txt documents
    $path = __DIR__.'/../EmbeddedMock/francetxt_paristxt.json';
    $rawFileContent = \file_get_contents($path);

    if (! $rawFileContent) {
        throw new RuntimeException('File not found');
    }

    $rawDocuments = \json_decode($rawFileContent, true);
    $embeddedDocuments = DocumentUtils::createDocumentsFromArray($rawDocuments);

    // Get the embedding of "France the country"
    $path = __DIR__.'/../EmbeddedMock/france_the_country_embedding.json';
    $rawFileContent = \file_get_contents($path);

    if (! $rawFileContent) {
        throw new RuntimeException('File not found');
    }

    /** @var float[] $embeddingQuery */
    $embeddingQuery = \json_decode($rawFileContent, true);
    $hosts = \explode(',', \getenv('OPENSEARCH_HOSTS') ?: '');

    if (empty(\array_filter($hosts))) {
        $hosts = ['https://localhost:9200'];
    }

    $client = (new ClientBuilder())::create()
        ->setHosts($hosts)
        ->setBasicAuthentication('admin', 'OpenSearch2.17')
        ->setSSLVerification(false)
        ->build();

    $vectorStore = new OpenSearchVectorStore($client, 'llphant_test');
    $vectorStore->addDocuments($embeddedDocuments);

    $searchResult1 = $vectorStore->similaritySearch($embeddingQuery, 2);
    expect(DocumentUtils::getFirstWordFromContent($searchResult1[0]))->toBe('France');

    $requestParam = [
        'filter' => [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'sourceName' => 'paris.txt',
                        ],
                    ],
                ],
            ],
        ],
    ];
    $searchResult2 = $vectorStore->similaritySearch($embeddingQuery, 2, $requestParam);
    expect(DocumentUtils::getFirstWordFromContent($searchResult2[0]))->toBe('Paris');
});

it('stores documents with conflicting metadata value types in bulk with OpenSearch', function () {
    $path = __DIR__.'/../EmbeddedMock/francetxt_paristxt.json';
    $rawFileContent = \file_get_contents($path);

    if (! $rawFileContent) {
        throw new RuntimeException('File not found');
    }

    $rawDocuments = \json_decode($rawFileContent, true);
    $embeddedDocuments = DocumentUtils::createDocumentsFromArray($rawDocuments);
    $documents = \array_slice($embeddedDocuments, 0, 2);

    $documents[0]->sourceName = 'metadata-number.txt';
    $documents[0]->metadata = ['value' => 1];
    $documents[1]->sourceName = 'metadata-object.txt';
    $documents[1]->metadata = ['value' => ['nested' => 1]];

    $hosts = \explode(',', \getenv('OPENSEARCH_HOSTS') ?: '');
    if (empty(\array_filter($hosts))) {
        $hosts = ['https://localhost:9200'];
    }

    $client = (new ClientBuilder())::create()
        ->setHosts($hosts)
        ->setBasicAuthentication('admin', 'OpenSearch2.17')
        ->setSSLVerification(false)
        ->build();

    $indexName = 'llphant_test_metadata_'.\bin2hex(\random_bytes(6));
    $vectorStore = new OpenSearchVectorStore($client, $indexName);
    /** @var float[] $queryEmbedding */
    $queryEmbedding = $documents[0]->embedding ?? [];

    $vectorStore->addDocuments($documents);

    $searchResult = $vectorStore->similaritySearch($queryEmbedding, 2);
    expect($searchResult)->toHaveCount(2);
});
