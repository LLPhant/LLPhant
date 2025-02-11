<?php

namespace Tests\Unit\Embeddings\VectorStores\Qdrant;

use ArrayAccess;
use ArrayIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use IteratorAggregate;
use LLPhant\Embeddings\VectorStores\Qdrant\QdrantVectorStore;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Qdrant;
use Traversable;

class History implements ArrayAccess, IteratorAggregate
{
    private $container = [];

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->container);
    }
}

class FakeQdrant extends Qdrant
{
    public const QDRANT_COLLECTION_LIST = <<<'JSON'
    {
        "result": [
            {
                "id": "c4ff4e3f62b63f67f34d3e64e7c53ca5f12dba0035bd471eae8f2ef0f5689432",
                "version": 0,
                "score": 0.83347666,
                "payload": {
                    "id": "c4ff4e3f62b63f67f34d3e64e7c53ca5f12dba0035bd471eae8f2ef0f5689432",
                    "content": "France (French: [fʁɑ̃s] Listen), officially the French Republic (French: République française [ʁepyblik fʁɑ̃sɛz]),[14] is a country located primarily in Western Europe. It also includes overseas regions and territories in the Americas and the Atlantic,Pacific and Indian Oceans,[XII] giving it one of the largest discontiguous exclusive economic zones in the world.",
                    "formattedContent": "The name of the source is: france.txt.France (French: [fʁɑ̃s] Listen), officially the French Republic (French: République française [ʁepyblik fʁɑ̃sɛz]),[14] is a country located primarily in Western Europe. It also includes overseas regions and territories in the Americas and the Atlantic,Pacific and Indian Oceans,[XII] giving it one of the largest discontiguous exclusive economic zones in the world.",
                    "sourceType": "files",
                    "sourceName": "france.txt",
                    "hash": "5b2e0d71b7355c6ac802fb259b0bd822e54770b0949ec379363932eac1ef28fa",
                    "chunkNumber": 1
                }
            },
            {
                "id": "3a2dd7a8d86884ddc79569aaaba2f58d1e1fe2096aa2fbfc0a622008891aba34",
                "version": 0,
                "score": 0.83347666,
                "payload": {
                    "id": "3a2dd7a8d86884ddc79569aaaba2f58d1e1fe2096aa2fbfc0a622008891aba34",
                    "content": "The house is on fire",
                    "formattedContent": "The name of the source is: france.txt.The house is on fire",
                    "sourceType": "files",
                    "sourceName": "france.txt",
                    "hash": "5773aa044e790575ee1a9cbefff632c6c643881f493b62fb6dd7b2ca1b2b1861",
                    "chunkNumber": 2
                }
            }
        ]
    }
    JSON;

    public function __construct(public QdrantVectorStore $qdrantStore, public $history)
    {
    }

    public static function create(string $body): FakeQdrant
    {
        $mock = new MockHandler([
            new Response(200, ['content-type' => 'application/json'], $body),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $history = new History([]);
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = new Client(['handler' => $handlerStack]);
        $config = new Config('fakeHost', 1111);
        $qdrantStore = new QdrantVectorStore($config, 'collection');
        $qdrantStore->setClient(new Qdrant(new Transport($client, $config)));

        return new FakeQdrant($qdrantStore, $history);

    }
}
