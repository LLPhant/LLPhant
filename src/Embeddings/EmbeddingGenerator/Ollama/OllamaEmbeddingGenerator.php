<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\Ollama;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\OllamaConfig;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class OllamaEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public ClientInterface $client;

    private readonly RequestFactoryInterface
        &StreamFactoryInterface $factory;

    private readonly string $model;

    private readonly string $baseUri;

    private readonly ?string $apiKey;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    public function __construct(
        OllamaConfig $config,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->model = $config->model;

        $this->apiKey = $config->apiKey;

        $this->baseUri = $config->url;
        if ($client instanceof ClientInterface) {
            $this->client = $client;
        } elseif ($config->timeout !== null) {
            $options = [
                'timeout' => $config->timeout,
                'connect_timeout' => $config->timeout,
                'read_timeout' => $config->timeout,
            ];
            $this->client = new GuzzleClient($options);
        } else {
            $this->client = Psr18ClientDiscovery::find();
        }
        $this->factory = new Psr17Factory(
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );
        $this->modelOptions = $config->modelOptions;
    }

    /**
     * Call out to Ollama embedding endpoint.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = \str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $request = $this->factory->createRequest(
            'POST',
            rtrim($this->baseUri, '/').'/embed'
        );
        $request = $request->withHeader('Content-Type', 'application/json');

        if ($this->apiKey) {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->apiKey);
        }

        $parameters = [
            'model' => $this->model,
            'input' => $text,
        ];

        if ($this->modelOptions) {
            $parameters['options'] = $this->modelOptions;
        }

        $request = $request->withBody(
            $this->factory->createStream(
                json_encode($parameters, JSON_THROW_ON_ERROR)
            )
        );

        $response = $this->client->sendRequest($request);

        $searchResults = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($searchResults)) {
            throw new Exception("Request to Ollama didn't returned an array: ".$response->getBody()->getContents());
        }

        if (! isset($searchResults['embeddings'])) {
            throw new Exception("Request to Ollama didn't returned expected format: ".$response->getBody()->getContents());
        }

        return $searchResults['embeddings'][0];
    }

    public function embedDocument(Document $document): Document
    {
        $text = $document->formattedContent ?? $document->content;
        $document->embedding = $this->embedText($text);

        return $document;
    }

    /**
     * @param  Document[]  $documents
     * @return Document[]
     */
    public function embedDocuments(array $documents): array
    {
        $embedDocuments = [];
        foreach ($documents as $document) {
            $embedDocuments[] = $this->embedDocument($document);
        }

        return $embedDocuments;
    }

    public function getEmbeddingLength(): int
    {
        return 1024;
    }
}
