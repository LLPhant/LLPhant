<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\LmStudio;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\LmStudioConfig;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LmStudioEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public ClientInterface $client;

    private readonly RequestFactoryInterface
        &StreamFactoryInterface $factory;

    private readonly string $model;

    private readonly string $baseUri;

    private readonly ?string $apiKey;

    public function __construct(
        LmStudioConfig $config,
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
    }

    /**
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = \str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $request = $this->factory->createRequest(
            'POST',
            rtrim($this->baseUri, '/').'/v1/embeddings'
        );
        $request = $request->withHeader('Content-Type', 'application/json');

        if ($this->apiKey) {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->apiKey);
        }

        $parameters = [
            'model' => $this->model,
            'input' => $text,
        ];

        $request = $request->withBody(
            $this->factory->createStream(
                json_encode($parameters, JSON_THROW_ON_ERROR)
            )
        );

        $response = $this->client->sendRequest($request);
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (! isset($json['data'][0]['embedding'])) {
            throw new Exception("Request to LM Studio didn't return expected format: ".json_encode($json, JSON_THROW_ON_ERROR));
        }

        return $json['data'][0]['embedding'];
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
        return 0; // LM Studio depends on the loaded model
    }
}
