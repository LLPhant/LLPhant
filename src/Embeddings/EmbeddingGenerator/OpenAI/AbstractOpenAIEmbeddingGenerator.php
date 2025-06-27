<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\OpenAI;

use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Exception\MissingParameterException;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class AbstractOpenAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public ClientContract $client;

    public int $batch_size_limit = 50;

    public string $apiKey;

    protected string $uri;

    private readonly StreamFactoryInterface
        &RequestFactoryInterface $factory;

    /**
     * @throws Exception
     */
    public function __construct(
        OpenAIConfig $config = new OpenAIConfig(),
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        if (! $config->apiKey) {
            throw new MissingParameterException('You have to provide an api key.');
        }
        $this->apiKey = $config->apiKey;

        if (! $config->url) {
            throw new MissingParameterException('You have to provide an url.');
        }
        $this->uri = $config->url.'/embeddings';

        if ($config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            $this->client = OpenAI::factory()
                ->withApiKey($this->apiKey)
                ->withBaseUri($config->url)
                ->make();
        }

        $this->factory = new Psr17Factory(
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );
    }

    /**
     * Call out to OpenAI's embedding endpoint.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $response = $this->client->embeddings()->create([
            'model' => $this->getModelName(),
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
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
     *
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws Exception
     */
    public function embedDocuments(array $documents): array
    {
        $clientForBatch = $this->createClientForBatch();

        $texts = array_map('LLPhant\Embeddings\DocumentUtils::getUtf8Data', $documents);

        // We create batches of 50 texts to avoid hitting the limit
        if ($this->batch_size_limit <= 0) {
            throw new Exception('Batch size limit must be greater than 0.');
        }

        $chunks = array_chunk($texts, $this->batch_size_limit);

        foreach ($chunks as $chunkKey => $chunk) {
            $body = [
                'model' => $this->getModelName(),
                'input' => $chunk,
            ];

            $request = $this->factory->createRequest('POST', $this->uri)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json')
                ->withHeader('Authorization', 'Bearer '.$this->apiKey)
                ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
            $response = $clientForBatch->sendRequest($request);
            $jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (\array_key_exists('data', $jsonResponse)) {
                foreach ($jsonResponse['data'] as $key => $oneEmbeddingObject) {
                    $documents[$chunkKey * $this->batch_size_limit + $key]->embedding = $oneEmbeddingObject['embedding'];
                }
            }
        }

        return $documents;
    }

    abstract public function getEmbeddingLength(): int;

    abstract public function getModelName(): string;

    protected function createClientForBatch(): ClientInterface
    {
        if ($this->apiKey === '' || $this->apiKey === '0') {
            throw new Exception('You have to provide an $apiKey to batch embeddings.');
        }

        return Psr18ClientDiscovery::find();
    }
}
