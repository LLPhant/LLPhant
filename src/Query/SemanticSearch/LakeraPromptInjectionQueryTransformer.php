<?php

namespace LLPhant\Query\SemanticSearch;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Exception\SecurityException;
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
        if ($endpoint === null && is_string(getenv('LAKERA_ENDPOINT'))) {
            $endpoint = getenv('LAKERA_ENDPOINT');
        }
        $this->endpoint = $endpoint ?? throw new \Exception('You have to provide a LAKERA_ENDPOINT env var to connect to LAKERA.');

        if ($apiKey === null && is_string(getenv('LAKERA_API_KEY'))) {
            $apiKey = getenv('LAKERA_API_KEY');
        }
        $this->apiKey = $apiKey ?? throw new \Exception('You have to provide a LAKERA_API_KEY env var to connect to LAKERA.');

        $this->client = $client instanceof ClientInterface ? $client : Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory;
    }

    /**
     * {@inheritDoc}
     */
    public function transformQuery(string $query): array
    {
        $request = $this->factory->createRequest('POST', sprintf('%s/v1/prompt_injection', rtrim($this->endpoint, '/')))
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode(['input' => $query], JSON_THROW_ON_ERROR)));

        $response = $this->client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \Exception('Lakera API error: '.$response->getBody()->getContents());
        }

        $json = $response->getBody()->getContents();
        $responseArray = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (array_key_exists('results', $responseArray) && array_key_exists(0, $responseArray['results']) && array_key_exists('flagged', $responseArray['results'][0])) {
            if ($responseArray['results'][0]['flagged'] === true) {
                throw new SecurityException('Prompt flagged as insecure: '.$query);
            }

            return [$query];
        }

        throw new \Exception('Unexpected response from API: '.$json);
    }
}
