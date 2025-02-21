<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\VoyageAI;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\VoyageAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use Psr\Http\Client\ClientExceptionInterface;

use function getenv;
use function str_replace;

abstract class AbstractVoyageAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public ClientContract $client;

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

    /**
     * @throws Exception
     */
    public function __construct(?VoyageAIConfig $config = null)
    {
        if ($config instanceof VoyageAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            $apiKey = $config->apiKey ?? getenv('VOYAGE_AI_API_KEY');
            if (! $apiKey) {
                throw new Exception('You have to provide a VOYAGE_API_KEY env var to request VoyageAI.');
            }
            $url = $config->url ?? (getenv('VOYAGE_AI_BASE_URL') ?: 'https://api.voyageai.com/v1');

            $this->client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withBaseUri($url)
                ->make();
            $this->uri = $url.'/embeddings';
            $this->apiKey = $apiKey;
        }
    }

    /**
     * Call out to VoyageAI's embedding endpoint.
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
                'truncate' => $this->truncate,
            ];

            if ($this->retrievalOption !== null) {
                $body['input_type'] = $this->retrievalOption;
            }

            $options = [
                RequestOptions::JSON => $body,
            ];

            $response = $clientForBatch->request('POST', $this->uri, $options);
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

        return new GuzzleClient([
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }
}
