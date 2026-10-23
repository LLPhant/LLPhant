<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings\VectorStores\Meilisearch;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Meilisearch\MeilisearchVectorStore;
use LLPhant\Exception\MissingParameterException;
use Meilisearch\Client;
use RuntimeException;

function storeWithResponses(array $responses, array &$history): MeilisearchVectorStore
{
    $handler = HandlerStack::create(new MockHandler(array_map(
        function (array $response): Response {
            if (isset($response['hits'])) {
                $response += [
                    'processingTimeMs' => 0,
                    'query' => '',
                    'limit' => 20,
                    'offset' => 0,
                    'estimatedTotalHits' => count($response['hits']),
                ];
            }

            return new Response(200, ['Content-Type' => 'application/json'], json_encode($response, JSON_THROW_ON_ERROR));
        },
        $responses
    )));
    $handler->push(Middleware::history($history));

    return new MeilisearchVectorStore(new Client('http://localhost:7700', 'test-key', new HttpClient(['handler' => $handler])), 'documents', 'custom');
}

it('creates an index with the configured user-provided embedder', function () {
    $history = [];
    $store = storeWithResponses([
        ['taskUid' => 1], ['status' => 'succeeded'],
        ['taskUid' => 2], ['status' => 'succeeded'],
    ], $history);
    $store->createIndex(3);

    expect(json_decode((string) $history[0]['request']->getBody(), true))->toBe(['primaryKey' => 'id', 'uid' => 'documents'])
        ->and($history[2]['request']->getUri()->getPath())->toBe('/indexes/documents/settings/embedders')
        ->and(json_decode((string) $history[2]['request']->getBody(), true))->toBe(['custom' => ['source' => 'userProvided', 'dimensions' => 3]]);
});

it('upserts documents in one batch with deterministic ids and metadata', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'succeeded']], $history);
    $documents = DocumentUtils::documents('Hello', 'World');
    foreach ($documents as $document) {
        $document->embedding = [0.1, 0.2];
        $document->metadata = ['tags' => ['test']];
    }
    $store->addDocuments($documents);

    $body = json_decode((string) $history[0]['request']->getBody(), true);
    expect($body)->toHaveCount(2)
        ->and($body[0]['id'])->toBe(hash('sha256', 'Hellomanual:manual:0'))
        ->and($body[0]['_vectors'])->toBe(['custom' => [0.1, 0.2]])
        ->and($body[0]['metadata'])->toBe(['tags' => ['test']])
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getQuery())->toBe('primaryKey=id')
        ->and($history[1]['request']->getUri()->getPath())->toBe('/tasks/1');
});

it('adds a single document', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'succeeded']], $history);
    $document = DocumentUtils::document('Hello');
    $document->embedding = [0.1, 0.2];
    $store->addDocument($document);

    expect(json_decode((string) $history[0]['request']->getBody(), true))->toHaveCount(1);
});

it('rejects a batch containing missing embeddings before sending it', function () {
    $history = [];
    $store = storeWithResponses([], $history);
    $documents = DocumentUtils::documents('Hello', 'World');
    $documents[0]->embedding = [0.1, 0.2];
    expect(fn () => $store->addDocuments($documents))->toThrow(MissingParameterException::class)
        ->and($history)->toBe([]);
});

it('does not send empty batches', function () {
    $history = [];
    $store = storeWithResponses([], $history);
    $store->addDocuments([]);
    $store->deleteDocuments([]);
    expect($history)->toBe([]);
});

it('searches semantically with filters and restores document fields', function () {
    $history = [];
    $store = storeWithResponses([['hits' => [[
        'id' => 'one', 'content' => 'Hello', 'formattedContent' => 'Formatted',
        'hash' => 'hash', 'sourceName' => 'hello.txt', 'sourceType' => 'file',
        'chunkNumber' => 2, 'metadata' => ['tags' => ['test']],
    ]]]], $history);
    $documents = $store->similaritySearch([0.1, 0.2], 30, ['filter' => 'sourceType = file']);

    expect(json_decode((string) $history[0]['request']->getBody(), true))->toBe([
        'q' => '', 'filter' => 'sourceType = file', 'vector' => [0.1, 0.2],
        'limit' => 30, 'hybrid' => ['semanticRatio' => 1, 'embedder' => 'custom'],
    ])->and($documents)->toHaveCount(1)
        ->and($documents[0]->id)->toBe('one')
        ->and($documents[0]->content)->toBe('Hello')
        ->and($documents[0]->formattedContent)->toBe('Formatted')
        ->and($documents[0]->hash)->toBe('hash')
        ->and($documents[0]->sourceName)->toBe('hello.txt')
        ->and($documents[0]->sourceType)->toBe('file')
        ->and($documents[0]->chunkNumber)->toBe(2)
        ->and($documents[0]->metadata)->toBe(['tags' => ['test']]);
});

it('returns no documents when no vectors match', function () {
    $history = [];
    $store = storeWithResponses([['hits' => []]], $history);
    expect($store->similaritySearch([0.1, 0.2]))->toBe([]);
});

it('deletes a document using the same id as insertion', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'succeeded']], $history);
    $store->deleteDocument(DocumentUtils::document('Hello'));
    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/indexes/documents/documents/'.hash('sha256', 'Hellomanual:manual:0'));
});

it('deletes a batch of documents', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'succeeded']], $history);
    $store->deleteDocuments(DocumentUtils::documents('Hello', 'World'));
    expect($history[0]['request']->getUri()->getPath())->toBe('/indexes/documents/documents/delete-batch')
        ->and(json_decode((string) $history[0]['request']->getBody(), true))->toBe([
            hash('sha256', 'Hellomanual:manual:0'), hash('sha256', 'Worldmanual:manual:0'),
        ]);
});

it('deletes the index', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'succeeded']], $history);
    $store->deleteIndex();
    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/indexes/documents');
});

it('reports failed asynchronous tasks', function () {
    $history = [];
    $store = storeWithResponses([['taskUid' => 1], ['status' => 'failed', 'error' => ['message' => 'Invalid dimensions']]], $history);
    expect(fn () => $store->deleteIndex())->toThrow(RuntimeException::class, 'Invalid dimensions');
});
