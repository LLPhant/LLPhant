<?php

namespace LLPhant\Embeddings\VectorStores\Typesense;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class LLPhantTypesenseClient
{
    protected readonly ClientInterface $client;

    protected readonly StreamFactoryInterface&RequestFactoryInterface $factory;

    protected readonly string $baseUri;

    protected readonly string $apiKey;

    public function __construct(
        ?string $node = null,
        ?string $apiKey = null,
        ?ClientInterface $client = null
    ) {
        if ($node === null && is_string(getenv('TYPESENSE_NODE'))) {
            $node = getenv('TYPESENSE_NODE');
        }
        $this->baseUri = $node ?? 'http://localhost:8108';
        if ($apiKey === null && is_string(getenv('TYPESENSE_API_KEY'))) {
            $apiKey = getenv('TYPESENSE_API_KEY');
        }
        $this->apiKey = $apiKey ?? throw new \Exception('You have to provide a TYPESENSE_API_KEY env var to connect to Typesense.');
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory;
    }

    public function collectionExists(string $name): bool
    {
        $response = $this->sendRequest('GET', '/collections/'.$name, []);

        $status = $response->getStatusCode();
        if ($status === 404) {
            return false;
        }
        if ($status < 200 || $status >= 300) {
            throw new \Exception('Typesense API error: '.$response->getBody()->getContents());
        }

        return \array_key_exists('name', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function createCollection(string $name, int $embeddingLength, string $vectorName): void
    {
        $payload = [
            'name' => $name,
            'fields' => [
                [
                    'name' => $vectorName,
                    'type' => 'float[]',
                    'num_dim' => $embeddingLength,
                ],
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
                [
                    'name' => 'content',
                    'type' => 'string',
                ],
                [
                    'name' => 'hash',
                    'type' => 'string',
                ],
                [
                    'name' => 'sourceName',
                    'type' => 'string',
                ],
                [
                    'name' => 'sourceType',
                    'type' => 'string',
                ],
                [
                    'name' => 'chunkNumber',
                    'type' => 'int32',
                ],
            ],
        ];

        $this->sendRequest('POST', '/collections', $payload);
    }

    /**
     * @param  array<string, mixed>  $point
     *
     * @throws \JsonException
     */
    public function upsert(string $collectionName, array $point): void
    {
        $this->sendRequest('POST', '/collections/'.$collectionName.'/documents?action=upsert', $point);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    public function multiSearch(array $query): array
    {
        $response = $this->sendRequest('POST', '/multi_search', $query);

        return \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<mixed>  $body
     *
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    protected function sendRequest(string $method, string $path, array $body): ResponseInterface
    {
        $url = sprintf('%s/%s', rtrim($this->baseUri, '/'), ltrim($path, '/'));

        $request = $this->factory->createRequest($method, $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-TYPESENSE-API-KEY', $this->apiKey)
            ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));

        return $this->client->sendRequest($request);
    }
}
