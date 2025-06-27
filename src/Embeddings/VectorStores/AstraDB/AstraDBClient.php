<?php

namespace LLPhant\Embeddings\VectorStores\AstraDB;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Exception\HttpException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AstraDBClient
{
    private readonly string $endpoint;

    private readonly string $token;

    private readonly ClientInterface $client;

    private readonly StreamFactoryInterface&RequestFactoryInterface $factory;

    /**
     * @var array<string, array<string, array<string, true>>>
     */
    private const BODY = [
        'findCollections' => [
            'options' => [
                'explain' => true,
            ],
        ],
    ];

    public function __construct(
        ?string $endpoint = null,
        ?string $token = null,
        private readonly string $keySpace = 'default_keyspace',
        public readonly string $collectionName = 'default_collection',
        ?ClientInterface $client = null)
    {
        if ($endpoint === null && is_string(getenv('ASTRADB_ENDPOINT'))) {
            $endpoint = getenv('ASTRADB_ENDPOINT');
        }
        $this->endpoint = $endpoint ?? throw new \Exception('You have to provide a ASTRADB_ENDPOINT env var to connect to AstraDB.');
        if ($token === null && is_string(getenv('ASTRADB_TOKEN'))) {
            $token = getenv('ASTRADB_TOKEN');
        }
        $this->token = $token ?? throw new \Exception('You have to provide a ASTRADB_TOKEN env var to connect to AstraDB.');
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory;
    }

    public function createCollection(
        int $dimension = 1536,
        string $metricType = 'cosine',
    ): void {
        $body = [
            'createCollection' => [
                'name' => $this->collectionName,
                'options' => [
                    'vector' => [
                        'dimension' => $dimension,
                        'metric' => $metricType,
                    ],
                ],
            ],
        ];

        $this->sendRequest($body, forObjectInCollection: false);
    }

    public function collectionVectorDimension(): int
    {
        $response = $this->sendRequest(self::BODY, forObjectInCollection: false);

        /** @var array<string, array<string, mixed>> $collections */
        $collections = $response['status']['collections'];
        foreach ($collections as $collection) {
            if ($collection['name'] === $this->collectionName) {
                return $collection['options']['vector']['dimension'];
            }
        }

        return 0;
    }

    public function deleteCollection(): void
    {
        $body = [
            'deleteCollection' => [
                'name' => $this->collectionName,
            ],
        ];

        $this->sendRequest($body, forObjectInCollection: false);
    }

    /**
     * @param  array<array<string, mixed>>  $documents
     * @return string[] the ids of the inserted records
     */
    public function insertData(array $documents): array
    {
        $body = [
            'insertMany' => [
                'documents' => $documents,
            ],
        ];

        $result = $this->sendRequest($body, forObjectInCollection: true);

        return $result['status']['insertedIds'];
    }

    /**
     * @param  array<float>  $embedding
     * @return array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int}[]
     *
     * @throws \JsonException
     */
    public function similaritySearch(array $embedding, int $k): array
    {
        $body = [
            'find' => [
                'sort' => ['$vector' => $embedding],
                'projection' => [
                    '_id' => 1,
                    'content' => 1,
                    'formattedContent' => 1,
                    'sourceType' => 1,
                    'sourceName' => 1,
                    'hash' => 1,
                    'chunkNumber' => 1,
                    '$vector' => 1,
                ],
                'options' => [
                    'includeSimilarity' => false,
                    'includeSortVector' => false,
                    'limit' => $k,
                ],
            ],
        ];

        $result = $this->sendRequest($body, forObjectInCollection: true);

        /**
         * @param  array<string, mixed>  $documentValues
         * @return array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int}[]
         */
        $mapFunction = function (array $documentValues): array {
            // Add the new "column" 'embedding', since we can't rename the '$vector' one
            $documentValues['embedding'] = $documentValues['$vector'];

            return $documentValues;
        };

        /** @var array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int}[] $result */
        $result = array_map($mapFunction, $result['data']['documents']);

        return $result;
    }

    public function cleanCollection(): void
    {
        $body = [
            'deleteMany' => new class
            {
            },
        ];

        $this->sendRequest($body, forObjectInCollection: true);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     *
     * @throws \JsonException
     * @throws ClientExceptionInterface
     */
    private function sendRequest(array $body, bool $forObjectInCollection): array
    {
        $path = $forObjectInCollection ? $this->collectionName : '';

        $request = $this->factory->createRequest('POST', sprintf('%s/api/json/v1/%s/%s', $this->endpoint, $this->keySpace, $path))
            ->withHeader('Token', $this->token)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
        $response = $this->client->sendRequest($request);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new HttpException(sprintf(
                'AstraDB API error (%s): %s',
                $status,
                $response->getBody()->getContents(),
            ));
        }

        $result = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (\array_key_exists('errors', $result) || \array_key_exists('error', $result)) {
            throw new \Exception('AstraDB API error: '.\print_r($result, true));
        }

        return $result;
    }
}
