<?php

namespace Tests\Unit\Embeddings\VectorStores\Meilisearch;

use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Meilisearch\MeilisearchVectorStore;
use LLPhant\Exception\HttpException;
use LLPhant\Exception\MissingParameterException;

it('creates the index and declares a user-provided embedder', function () {
    $fakeClient = FakeMeilisearchClient::withResponses([
        new Response(404, [], '{"message":"Index `llphant` not found."}'),
        new Response(202, [], '{"taskUid":0}'),
        new Response(200, [], '{"uid":0,"status":"succeeded"}'),
        new Response(202, [], '{"taskUid":1}'),
        new Response(200, [], '{"uid":1,"status":"succeeded"}'),
    ]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);
    $vectorStore->createCollection(1536);

    expect($fakeClient->requestBody('/indexes/llphant/settings'))
        ->toContain('"source":"userProvided"')
        ->toContain('"dimensions":1536')
        ->and($fakeClient->requestedPaths())->toBe([
            '/indexes/llphant',
            '/indexes',
            '/tasks/0',
            '/indexes/llphant/settings',
            '/tasks/1',
        ]);
});

it('can insert documents', function () {
    $fakeClient = FakeMeilisearchClient::withResponses([
        new Response(202, [], '{"taskUid":2}'),
        new Response(200, [], '{"uid":2,"status":"succeeded"}'),
    ]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);

    $documents = DocumentUtils::createDocumentsFromArray([
        [
            'content' => 'Hello World!',
            'formattedContent' => 'Hello World!',
            'embedding' => [0.1, 0.3],
            'sourceType' => 'txt',
            'sourceName' => 'hello',
            'hash' => 'abc',
            'chunkNumber' => 0,
        ],
        [
            'content' => 'Ciao mondo!',
            'formattedContent' => 'Ciao mondo!',
            'embedding' => [0.21, 0.34],
            'sourceType' => 'txt',
            'sourceName' => 'hello_it',
            'hash' => 'xyz',
            'chunkNumber' => 1,
        ],
    ]);

    $vectorStore->addDocuments($documents);

    $requestBody = json_decode($fakeClient->requestBody('/indexes/llphant/documents'), true);

    expect($requestBody)->toHaveCount(2)
        ->and($requestBody[0]['content'])->toBe('Hello World!')
        ->and($requestBody[0]['_vectors']['default']['embeddings'])->toBe([0.1, 0.3])
        ->and($requestBody[0]['_vectors']['default']['regenerate'])->toBeFalse()
        ->and($requestBody[1]['chunkNumber'])->toBe(1)
        ->and($requestBody[0]['id'])->toBeString();
});

it('can perform similarity search', function () {
    $hits = <<<'JSON'
    {
      "hits": [
        {
          "id": "e0f1",
          "content": "France is a country in Europe",
          "formattedContent": "France is a country in Europe",
          "sourceType": "txt",
          "sourceName": "france",
          "hash": "abc",
          "chunkNumber": 0,
          "metadata": {"lang": "en"},
          "_vectors": {"default": {"embeddings": [[0.9, 0.1]], "regenerate": false}},
          "_rankingScore": 0.98
        },
        {
          "id": "a2b3",
          "content": "The house is on fire",
          "sourceType": "txt",
          "sourceName": "house",
          "hash": "xyz",
          "chunkNumber": 3,
          "_vectors": {"default": [0.1, 0.9]},
          "_rankingScore": 0.42
        }
      ]
    }
    JSON;

    $fakeClient = FakeMeilisearchClient::withResponses([new Response(200, [], $hits)]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);

    $similarDocuments = $vectorStore->similaritySearch([0.9, 0.1], 2);

    $ids = [];
    foreach ($similarDocuments as $document) {
        $ids[] = $document->id;
    }

    expect($ids)->toBe(['e0f1', 'a2b3']);

    expect($similarDocuments)->toHaveCount(2)
        ->and($similarDocuments[0]->content)->toBe('France is a country in Europe')
        ->and($similarDocuments[0]->metadata)->toBe(['lang' => 'en'])
        ->and($similarDocuments[0]->embedding)->toBe([0.9, 0.1])
        ->and($similarDocuments[1]->content)->toBe('The house is on fire')
        ->and($similarDocuments[1]->formattedContent)->toBeNull()
        ->and($similarDocuments[1]->chunkNumber)->toBe(3)
        ->and($similarDocuments[1]->metadata)->toBe([]);

    $requestBody = json_decode($fakeClient->lastRequestBody(), true);

    expect($requestBody['vector'])->toBe([0.9, 0.1])
        ->and($requestBody['limit'])->toBe(2)
        ->and($requestBody['hybrid'])->toBe(['semanticRatio' => 1, 'embedder' => 'default']);
});

it('waits for a queued write task before returning', function () {
    $fakeClient = FakeMeilisearchClient::withResponses([
        new Response(202, [], '{"taskUid":7,"status":"enqueued"}'),
        new Response(200, [], '{"uid":7,"status":"enqueued"}'),
        new Response(200, [], '{"uid":7,"status":"processing"}'),
        new Response(200, [], '{"uid":7,"status":"succeeded"}'),
    ]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);
    $vectorStore->addDocuments(DocumentUtils::createDocumentsFromArray([
        [
            'content' => 'Hello World!',
            'formattedContent' => 'Hello World!',
            'embedding' => [0.1, 0.3],
            'sourceType' => 'txt',
            'sourceName' => 'hello',
            'hash' => 'abc',
            'chunkNumber' => 0,
        ],
    ]));

    expect($fakeClient->requestedPaths())->toBe([
        '/indexes/llphant/documents',
        '/tasks/7',
        '/tasks/7',
        '/tasks/7',
    ]);
});

it('raises the error of a failed write task', function () {
    $fakeClient = FakeMeilisearchClient::withResponses([
        new Response(202, [], '{"taskUid":9,"status":"enqueued"}'),
        new Response(200, [], '{"uid":9,"status":"failed","error":{"message":"The provided vectors should have 2 dimensions, but got 3","code":"invalid_vector_dimensions"}}'),
    ]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);
    $vectorStore->addDocuments(DocumentUtils::createDocumentsFromArray([
        [
            'content' => 'Hello World!',
            'formattedContent' => 'Hello World!',
            'embedding' => [0.1, 0.3],
            'sourceType' => 'txt',
            'sourceName' => 'hello',
            'hash' => 'abc',
            'chunkNumber' => 0,
        ],
    ]));
})->throws(HttpException::class, 'The provided vectors should have 2 dimensions, but got 3');

it('refuses a document without vectors', function () {
    $fakeClient = FakeMeilisearchClient::withResponses([]);

    $vectorStore = new MeilisearchVectorStore(client: $fakeClient->client);

    $documents = DocumentUtils::createDocumentsFromArray([
        [
            'content' => 'Hello World!',
            'formattedContent' => 'Hello World!',
            'embedding' => [],
            'sourceType' => 'txt',
            'sourceName' => 'hello',
            'hash' => 'abc',
            'chunkNumber' => 0,
        ],
    ]);
    $documents[0]->embedding = null;

    $vectorStore->addDocuments($documents);
})->throws(MissingParameterException::class);
