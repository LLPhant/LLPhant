<?php

namespace LLPhant\Query\SemanticSearch;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Exception\SecurityException;
use LLPhant\Utility;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class LakeraPromptInjectionQueryTransformer implements QueryTransformer
{
    public ClientInterface $client;

    public RequestFactoryInterface&StreamFactoryInterface $factory;

    public string $endpoint;

    public string $apiKey;

    public function __construct(
        ?string $endpoint = 'https://api.lakera.ai/',
        ?string $apiKey = null,
        ?ClientInterface $client = null)
    {
        $endpoint ??= Utility::readEnvironment('LAKERA_ENDPOINT');
        $this->endpoint = $endpoint ?? throw new \Exception('You have to provide a LAKERA_ENDPOINT env var to connect to LAKERA.');

        $apiKey ??= Utility::readEnvironment('LAKERA_API_KEY');
        $this->apiKey = $apiKey ?? throw new \Exception('You have to provide a LAKERA_API_KEY env var to connect to LAKERA.');

        $this->client = $client instanceof ClientInterface ? $client : Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory;
    }

    /**
     * {@inheritDoc}
     */
    public function transformQuery(string $query): array
    {
        $request = $this->factory->createRequest('POST', sprintf('%s/v2/guard', rtrim($this->endpoint, '/')))
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream($this->apiPayloadFor($query)));

        $response = $this->client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \Exception('Lakera API error: '.$response->getBody()->getContents());
        }

        $json = $response->getBody()->getContents();
        $responseArray = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (array_key_exists('flagged', $responseArray)) {
            if ($responseArray['flagged'] === true) {
                throw new SecurityException('Prompt flagged as insecure: '.$query);
            }

            return [$query];
        }

        throw new \Exception('Unexpected response from API: '.$json);
    }

    private function apiPayloadFor(string $query): string
    {
        $result = json_encode(['messages' => [['role' => 'user', 'content' => $query]]], JSON_THROW_ON_ERROR);
        if ($result === false) {
            throw new \Exception('Failed to encode query: '.$query);
        }

        return $result;
    }
}
