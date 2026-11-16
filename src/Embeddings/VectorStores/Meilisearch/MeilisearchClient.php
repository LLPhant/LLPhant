<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\VectorStores\Meilisearch;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Exception\HttpException;
use LLPhant\Utility;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The Meilisearch calls a vector store needs: index creation, the embedder setting and search.
 */
class MeilisearchClient
{
    final public const DEFAULT_HOST = 'http://localhost:7700';

    private readonly string $host;

    private readonly ?string $key;

    private readonly ClientInterface $client;

    private readonly StreamFactoryInterface&RequestFactoryInterface $factory;

    public function __construct(?string $host = null, ?string $key = null, ?ClientInterface $client = null)
    {
        $this->host = rtrim($host ?? Utility::readEnvironment('MEILISEARCH_HOST') ?? self::DEFAULT_HOST, '/');
        $this->key = $key ?? Utility::readEnvironment('MEILISEARCH_KEY');
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory();
    }

    public function indexExists(string $indexUid): bool
    {
        $response = $this->client->sendRequest($this->request('GET', sprintf('/indexes/%s', $indexUid)));
        $body = $response->getBody()->getContents();

        if ($response->getStatusCode() === 404) {
            return false;
        }

        $this->assertSuccessful($response->getStatusCode(), $body);

        return true;
    }

    public function createIndex(string $indexUid): void
    {
        $this->send('POST', '/indexes', ['uid' => $indexUid, 'primaryKey' => 'id']);
    }

    /**
     * Meilisearch only searches vectors it knows the shape of: the store computes them itself, so the
     * embedder has to be declared as user-provided with the dimensions of those vectors.
     */
    public function setUserProvidedEmbedder(string $indexUid, string $embedder, int $dimensions): void
    {
        $this->send('PATCH', sprintf('/indexes/%s/settings', $indexUid), [
            'embedders' => [
                $embedder => ['source' => 'userProvided', 'dimensions' => $dimensions],
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    public function addDocuments(string $indexUid, array $documents): void
    {
        $this->send('PUT', sprintf('/indexes/%s/documents', $indexUid), $documents);
    }

    /**
     * @param  float[]  $embedding
     * @return array<int, array<string, mixed>>
     */
    public function search(string $indexUid, array $embedding, int $k, string $embedder): array
    {
        $result = $this->send('POST', sprintf('/indexes/%s/search', $indexUid), [
            'vector' => $embedding,
            'limit' => $k,
            'retrieveVectors' => true,
            'showRankingScore' => true,
            'hybrid' => ['semanticRatio' => 1.0, 'embedder' => $embedder],
        ]);

        $hits = $result['hits'] ?? null;
        if (! is_array($hits)) {
            return [];
        }

        $documents = [];
        foreach ($hits as $hit) {
            if (is_array($hit)) {
                /** @var array<string, mixed> $hit */
                $documents[] = $hit;
            }
        }

        return $documents;
    }

    /**
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $body
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $body): array
    {
        return $this->decode($this->sendRequest($this->request($method, $path, $body)));
    }

    /**
     * @param  array<string, mixed>|array<int, array<string, mixed>>|null  $body
     */
    private function request(string $method, string $path, ?array $body = null): RequestInterface
    {
        $request = $this->factory->createRequest($method, $this->host.$path)
            ->withHeader('Accept', 'application/json');

        if ($this->key !== null) {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->key);
        }

        if ($body === null) {
            return $request;
        }

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    private function sendRequest(RequestInterface $request): string
    {
        $response = $this->client->sendRequest($request);
        $body = $response->getBody()->getContents();
        $this->assertSuccessful($response->getStatusCode(), $body);

        return $body;
    }

    private function assertSuccessful(int $status, string $body): void
    {
        if ($status < 200 || $status >= 300) {
            throw new HttpException(sprintf('Meilisearch API error (%d): %s', $status, $body));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $body): array
    {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new HttpException('Meilisearch API error: the response is not a JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
