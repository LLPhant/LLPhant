<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\VoyageAI;

use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Utility;
use LLPhant\VoyageAIConfig;
use OpenAI\Contracts\ClientContract;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function str_replace;

abstract class AbstractVoyageAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public ClientInterface $client;

    public int $batch_size_limit = 128;

    public string $apiKey;

    /**
     * Whether to use the retrieval-optimized embedding.
     * Can be "query" or "document".
     */
    public ?string $retrievalOption = null;

    /**
     * Whether to truncate the text automatically by the API
     * to fit the model's maximum input length.
     */
    public bool $truncate = true;

    protected string $uri = 'https://api.voyageai.com/v1/embeddings';

    private readonly RequestFactoryInterface
        &StreamFactoryInterface $factory;

    /**
     * @throws Exception
     */
    public function __construct(
        ?VoyageAIConfig $config = null,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        if ($config instanceof VoyageAIConfig && $config->client instanceof ClientContract) {
            throw new \RuntimeException('Passing a client to a VoyageAIConfig is no more admitted.');
        }
        $apiKey = Utility::readEnvironment('VOYAGE_AI_API_KEY');
        if (! $apiKey) {
            throw new Exception('You have to provide a VOYAGE_API_KEY env var to request VoyageAI.');
        }
        $url = $config->url ?? Utility::readEnvironment('VOYAGE_AI_BASE_URL', 'https://api.voyageai.com/v1');
        $this->uri = $url.'/embeddings';
        $this->apiKey = $apiKey;
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory(
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );
    }

    /**
     * Call out to VoyageAI's embedding endpoint.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $body = [
            'model' => $this->getModelName(),
            'input' => $text,
            'truncation' => $this->truncate,
        ];

        if ($this->retrievalOption !== null) {
            $body['input_type'] = $this->retrievalOption;
        }

        $response = $this->client->sendRequest($this->createPostRequest($body));
        $jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $result = [];

        if (\array_key_exists('data', $jsonResponse)) {
            foreach ($jsonResponse['data'] as $oneEmbeddingObject) {
                $result = $oneEmbeddingObject['embedding'];
            }
        }

        return $result;
    }

    /**
     * Mark the embedding as optimized for retrieval.
     * Use this on your queries/questions about the documents you already embedded.
     *
     * @return $this
     */
    public function forRetrieval(): self
    {
        $this->retrievalOption = 'query';

        return $this;
    }

    /**
     * Mark the embedding as optimized for retrieval.
     * Use this on your documents before inserting them into the vector database.
     */
    public function forStorage(): self
    {
        $this->retrievalOption = 'document';

        return $this;
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
                'truncation' => $this->truncate,
            ];

            if ($this->retrievalOption !== null) {
                $body['input_type'] = $this->retrievalOption;
            }

            $response = $this->client->sendRequest($this->createPostRequest($body));
            $jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (\array_key_exists('data', $jsonResponse)) {
                foreach ($jsonResponse['data'] as $key => $oneEmbeddingObject) {
                    $documents[$chunkKey * $this->batch_size_limit + $key]->embedding = $oneEmbeddingObject['embedding'];
                }
            }
        }

        return $documents;
    }

    /**
     * @param  array<array-key, mixed>  $body
     * @param  array<array-key, string>  $headers
     */
    private function createPostRequest(array $body, array $headers = []): RequestInterface
    {
        $request = $this->factory->createRequest('POST', $this->uri)
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        foreach ($headers as $name => $value) {
            // Headers could be overridden
            $request = $request->withHeader($name, $value);
        }

        return $request->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    abstract public function getEmbeddingLength(): int;

    abstract public function getModelName(): string;
}
