<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator\Ollama;

use Exception;
use GuzzleHttp\Client;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\OllamaConfig;

use function str_replace;

final class OllamaEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public Client $client;

    private readonly string $model;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    public function __construct(OllamaConfig $config)
    {
        $this->model = $config->model;

        $options = [
            'base_uri' => $config->url,
            'timeout' => $config->timeout,
            'connect_timeout' => $config->timeout,
            'read_timeout' => $config->timeout,
        ];

        if (! empty($config->apiKey)) {
            $options['headers'] = ['Authorization' => 'Bearer '.$config->apiKey];
        }

        $this->client = new Client($options);

        $this->modelOptions = $config->modelOptions;
    }

    /**
     * Call out to Ollama embedding endpoint.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = str_replace("\n", ' ', DocumentUtils::toUtf8($text));

        $params = [
            ...$this->modelOptions,
            'model' => $this->model,
            'input' => $text,
        ];

        $response = $this->client->post('embed', [
            'body' => json_encode($params, JSON_THROW_ON_ERROR),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

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
        return $this->modelOptions['options']['num_ctx'] ?? 2048;
    }
}
