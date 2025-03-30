<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\Gemini;

use Exception;
use Gemini;
use Gemini\Contracts\ClientContract;
use Gemini\Contracts\Resources\EmbeddingModalContract;
use Gemini\Enums\ModelType;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\GeminiConfig;

use function getenv;
use function str_replace;

/**
 * Embedding generator for Gemini API.
 */
final class GeminiEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    /** @var ClientContract|null $client */
    private readonly ClientContract $client;

    /** @var string $model */
    private string $model;

    /**
     * @param GeminiConfig|null $config
     * @throws Exception
     */
    public function __construct(?GeminiConfig $config = null)
    {
        if ($config instanceof GeminiConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            $this->client = $this->buildClient($config);
        }

        $this->model = $config->model ?? ModelType::EMBEDDING->value;
    }

    /**
     * Build the Gemini client.
     *
     * @param GeminiConfig|null $config The configuration for the Gemini API
     * @return ClientContract The Gemini client
     * @throws Exception
     */
    protected function buildClient(?GeminiConfig $config): ClientContract
    {
        $clientFactory = Gemini::factory();

        $apiKey = $config->apiKey ?? getenv('GEMINI_API_KEY');
        if (!$apiKey) {
            throw new Exception('You have to provide a GEMINI_API_KEY env var to request Gemini API.');
        }
        $clientFactory->withApiKey($apiKey);

        $baseUrl = $config->url ?? getenv('GEMINI_BASE_URL');
        if (!empty($baseUrl)) {
            $clientFactory->withBaseUrl($baseUrl);
        }

        return $clientFactory->make();
    }

    /**
     * @return string The model to use
     */
    protected function getModel(): string {
        return $this->model;
    }

    /**
     * @return EmbeddingModalContract The embedding model
     */
    protected function getEmbeddingModel(): EmbeddingModalContract {
        return $this->client->embeddingModel($this->getModel());
    }

    /**
     * @param string $text The text to embed
     * @return array|float[] The embedding of the text
     */
    public function embedText(string $text): array
    {
        $text = str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $response = $this->getEmbeddingModel()->embedContent($text);

        return $response->embedding->values;
    }

    /**
     * @param Document $document The document to embed
     * @return Document The document with the embedding
     */
    public function embedDocument(Document $document): Document
    {
        $text = $document->formattedContent ?? $document->content;
        $document->embedding = $this->embedText($text);

        return $document;
    }

    /**
     * @param Document[] $documents The documents to embed
     * @return Document[] The documents with the embeddings
     */
    public function embedDocuments(array $documents): array
    {
        $embedDocuments = [];
        foreach ($documents as $document) {
            $embedDocuments[] = $this->embedDocument($document);
        }

        return $embedDocuments;
    }

    /**
     * @return int The length of the embedding
     */
    public function getEmbeddingLength(): int
    {
        return 768;
    }
}
