<?php

namespace Tests\Unit\Embeddings\VectorStores\TypeSense;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\VectorStores\Typesense\LLPhantTypesenseClient;

final class FakeTypesenseClientFactory
{
    private function __construct()
    {
    }

    public static function typesenseClientWithFakeHttpConnection(string ...$bodies): LLPhantTypesenseClient
    {
        $responses = [];
        foreach ($bodies as $body) {
            $responses[] = new Response(200, [], $body);
        }
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new LLPhantTypesenseClient(apiKey: 'fake', client: $client);
    }
}
