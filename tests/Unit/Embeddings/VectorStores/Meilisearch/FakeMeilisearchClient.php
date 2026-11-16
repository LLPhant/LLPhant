<?php

namespace Tests\Unit\Embeddings\VectorStores\Meilisearch;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\VectorStores\Meilisearch\MeilisearchClient;

class FakeMeilisearchClient
{
    /**
     * @param  Response[]  $responses
     */
    public function __construct(public readonly MeilisearchClient $client, public readonly MockHandler $handler)
    {
    }

    /**
     * @param  Response[]  $responses
     */
    public static function withResponses(array $responses): self
    {
        $mock = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new self(new MeilisearchClient(host: 'http://meilisearch.test', client: $client), $mock);
    }

    public function lastRequestBody(): string
    {
        $request = $this->handler->getLastRequest();

        return $request instanceof \Psr\Http\Message\RequestInterface ? $request->getBody()->getContents() : '';
    }
}
