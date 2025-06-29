<?php

namespace Tests\Unit\Embeddings\VectorStores\AstraDB;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\VectorStores\AstraDB\AstraDBClient;

class FakeAstraClient
{
    public const ASTRA_COLLECTION_LIST = <<<'JSON'
    {
      "status": {
        "collections": [
          {
            "name": "collection_1536",
            "options": {
              "vector": {
                "dimension": 1536,
                "metric": "cosine"
              }
            }
          },
          {
            "name": "default_collection",
            "options": {
              "vector": {
                "dimension": 1024,
                "metric": "cosine"
              }
            }
          },
          {
            "name": "my_store",
            "options": {
              "vector": {
                "dimension": 1536,
                "metric": "cosine"
              },
              "indexing": {
                "allow": [
                  "metadata"
                ]
              }
            }
          }
        ]
      }
    }
    JSON;

    public const ASTRA_COLLECTION_EMPTY_LIST = <<<'JSON'
    {
      "status": {
        "collections": []
      }
    }
    JSON;

    public const ASTRA_INSERTED_IDS = <<<'JSON'
    {
       "status": {
          "insertedIds": [
             "4",
             "7"
          ]
       }
    }
    JSON;

    public function __construct(public readonly AstraDBClient $client, public readonly MockHandler $handler)
    {
    }

    public static function astraDBClientWithFakeHttpConnection(string $body): FakeAstraClient
    {
        $mock = new MockHandler([
            new Response(200, [], $body),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new FakeAstraClient(new AstraDBClient(endpoint: '', token: '', client: $client), $mock);
    }
}
