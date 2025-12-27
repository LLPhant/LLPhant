<?php

namespace Tests\Unit\Embeddings\EmbeddingGenerator\LmStudio;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator;
use LLPhant\LmStudioConfig;

it('embed a text', function () {
    $config = new LmStudioConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new LmStudioEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"data": [{"embedding": [1, 2, 3]}]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    $generator->client = $client;

    expect($generator->embedText('this is the text to embed'))->toBeArray();
});

it('embed a document', function () {
    $config = new LmStudioConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new LmStudioEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"data": [{"embedding": [1, 2, 3]}]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    $generator->client = $client;

    $document = new Document();
    $document->formattedContent = 'this is the text to embed';
    expect($generator->embedDocument($document))->toBeInstanceOf(Document::class);
});

it('embed documents', function () {
    $config = new LmStudioConfig();
    $config->model = 'fake-model';
    $config->url = 'http://fakeurl';
    $generator = new LmStudioEmbeddingGenerator($config);

    $mock = new MockHandler([
        new Response(200, [], '{"data": [{"embedding": [1, 2, 3]}]}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleClient(['handler' => $handlerStack]);

    $generator->client = $client;

    $document = new Document();
    $document->formattedContent = 'this is the text to embed';

    $result = $generator->embedDocuments([$document]);
    expect($result)->toBeArray();
    expect($result[0])->toBeInstanceOf(Document::class);
});
