<?php

namespace LLPhant\Embeddings\VectorStores\Milvus;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class MilvusClient
{
    final public const API_VERSION = 'v1';

    public ClientInterface $client;

    public StreamFactoryInterface&RequestFactoryInterface $factory;

    public string $baseUri;

    public string $authorization;

    public function __construct(
        public string $host,
        public string $port,
        public string $user,
        public string $password,
        public string $database = 'default',
        public string $apiVersion = self::API_VERSION
    ) {
        $this->client = Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory;
        $this->baseUri = sprintf('http://%s:%s/%s/', $host, $port, $apiVersion);
        $this->authorization = sprintf('Basic %s:%s', $user, $password);
    }

    /**
     * @return array{code: int, data: mixed}
     */
    public function listCollections(): array
    {
        $path = 'vector/collections';

        return $this->sendRequest('GET', $path);
    }

    /**
     * @return array{code: int, data: mixed}
     */
    public function dropCollection(string $collectionName): array
    {
        $path = 'vector/collections/drop';
        $body = [
            'collectionName' => $collectionName,
        ];

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @return array{code: int, data: mixed}
     */
    public function createCollection(
        string $collectionName,
        int $dimension,
        string $metricType,
        string $primaryField,
        string $vectorField
    ): array {
        $path = 'vector/collections/create';
        $body = [
            'dbName' => $this->database,
            'collectionName' => $collectionName,
            'dimension' => $dimension,
            'metricType' => $metricType,
            'primaryField' => $primaryField,
            'vectorField' => $vectorField,
        ];

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array{code: int, data: mixed}
     */
    public function insertData(string $collectionName, array $data): array
    {
        $path = 'vector/insert';
        $body = [
            'collectionName' => $collectionName,
            'data' => $data,
        ];

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @param  string[]|null  $outputFields
     * @param  float[]  $vector
     * @return array{code: int, data: mixed}
     */
    public function searchVector(
        string $collectionName,
        array $vector,
        int $limit,
        ?string $filter = null,
        ?array $outputFields = null
    ): array {
        $path = 'vector/search';
        $body = [
            'collectionName' => $collectionName,
            'vector' => $vector,
            'limit' => $limit,
        ];
        if ($outputFields !== null) {
            $body['outputFields'] = $outputFields;
        }
        if ($filter !== null) {
            $body['filter'] = $filter;
        }

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @return array{code: int, data: mixed}
     */
    public function deleteCollection(string $collectionName): array
    {
        $path = 'vector/collections/drop';
        $body = [
            'collectionName' => $collectionName,
        ];

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @param  string[]|null  $outputFields
     * @return array{code: int, data: mixed}
     */
    public function query(string $collectionName, ?array $outputFields = null, ?string $filter = null, int $limit = 100): array
    {
        $path = 'vector/query';
        $body = [
            'collectionName' => $collectionName,
            'limit' => $limit,
        ];

        if ($outputFields !== null) {
            $body['outputFields'] = $outputFields;
        }

        if ($filter !== null) {
            $body['filter'] = $filter;
        }

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @param  string[]|null  $outputFields
     * @return array{code: int, data: mixed}
     */
    public function getEntity(string $collectionName, string $id, ?array $outputFields = null): array
    {
        $path = 'vector/get';
        $body = [
            'collectionName' => $collectionName,
            'id' => $id,
        ];
        if ($outputFields !== null) {
            $body['outputFields'] = $outputFields;
        }

        return $this->sendRequest('POST', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{code: int, data: mixed}
     *
     * @throws ClientExceptionInterface|\JsonException
     */
    protected function sendRequest(string $method, string $path, array $body = []): array
    {
        $request = $this->factory->createRequest($method, $this->baseUri.$path)
            ->withHeader('Authorization', $this->authorization)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));

        $response = $this->client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $errorBody = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            throw new \Exception(
                sprintf(
                    'Milvus API error: %s',
                    $errorBody['error_msg']
                )
            );
        }

        /** @var array{code: int, data: mixed} */
        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
