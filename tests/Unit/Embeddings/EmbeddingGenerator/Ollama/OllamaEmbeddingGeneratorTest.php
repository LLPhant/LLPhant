<?php

namespace Tests\Unit\Embeddings\EmbeddingGenerator\Ollama;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\OllamaConfig;

it('embed a text', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"embeddings": [[1, 2, 3]]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    // override client for test
    $generator->client = $client;

    expect($generator->embedText('this is the text to embed'))->toBeArray();
});

it('embed a non UTF8 text', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"embeddings": [[1, 2, 3]]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    // override client for test
    $generator->client = $client;

    $japanese = \mb_convert_encoding('おはよう', 'EUC-JP', 'UTF-8');

    expect($generator->embedText($japanese))->toBeArray();
});

it('embed a document', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"embeddings": [[1, 2, 3]]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    // override client for test
    $generator->client = $client;

    $document = new Document();
    $document->formattedContent = 'this is the text to embed';
    expect($generator->embedDocument($document))->toBeInstanceOf(Document::class);
});

it('embed documents', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"embeddings": [[1, 2, 3]]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    // override client for test
    $generator->client = $client;

    $document = new Document();
    $document->formattedContent = 'this is the text to embed';

    $result = $generator->embedDocuments([$document]);
    expect($result)->toBeArray();
    expect($result[0])->toBeInstanceOf(Document::class);
});

it('returns embedding length from /show model_info', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $showResponse = json_encode([
        'model_info' => [
            'general.architecture' => 'nomic-bert',
            'nomic-bert.embedding_length' => 768,
        ],
    ]);

    $mock = new MockHandler([
        new Response(200, [], $showResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    $generator->client = $client;

    expect($generator->getEmbeddingLength())->toBe(768);
});

it('caches the embedding length after the first call', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new OllamaEmbeddingGenerator($config);

    $showResponse = json_encode([
        'model_info' => [
            'general.architecture' => 'nomic-bert',
            'nomic-bert.embedding_length' => 768,
        ],
    ]);

    // Only one response queued — second call must use cache, not make another request
    $mock = new MockHandler([
        new Response(200, [], $showResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    $generator->client = $client;

    expect($generator->getEmbeddingLength())->toBe(768);
    expect($generator->getEmbeddingLength())->toBe(768); // cached, no second HTTP call
});

it('can use timeout option', function () {
    $config = new OllamaConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $config->timeout = 99.0;
    $generator = new OllamaEmbeddingGenerator($config);

    expect($generator->client)->toBeInstanceOf(GuzzleClient::class);
    // This expectation will be removed when using next version of Guzzle
    expect($generator->client->getConfig()['connect_timeout'])->toBe(99.0);
});
