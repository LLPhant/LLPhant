<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\VectorStores\Meilisearch;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use LLPhant\Exception\MissingParameterException;

class MeilisearchVectorStore extends VectorStoreBase
{
    final public const MEILISEARCH_INDEX_NAME = 'llphant';

    final public const MEILISEARCH_EMBEDDER_NAME = 'default';

    public function __construct(
        private readonly string $indexUid = self::MEILISEARCH_INDEX_NAME,
        private readonly MeilisearchClient $client = new MeilisearchClient(),
        private readonly string $embedder = self::MEILISEARCH_EMBEDDER_NAME
    ) {
    }

    /**
     * Create the index and declare the vectors we compute ourselves as user-provided ones.
     *
     * Meilisearch refuses a vector search on an index whose embedder it does not know, and it cannot
     * infer the dimensions of vectors generated outside Meilisearch, so both belong to this call.
     *
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollection(int $embeddingLength): void
    {
        if (! $this->client->indexExists($this->indexUid)) {
            $this->client->createIndex($this->indexUid);
        }

        $this->client->setUserProvidedEmbedder($this->indexUid, $this->embedder, $embeddingLength);
    }

    /**
     * @throws MissingParameterException
     */
    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    /**
     * @param  Document[]  $documents
     *
     * @throws MissingParameterException
     */
    public function addDocuments(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $this->client->addDocuments(
            $this->indexUid,
            array_map(
                fn (Document $document): array => $this->toMeilisearchDocument($document),
                $documents
            )
        );
    }

    /**
     * @param  float[]  $embedding
     * @param  array<string, string|int>|array<mixed[]>  $additionalArguments
     * @return Document[]
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): iterable
    {
        return array_map(
            fn (array $hit): Document => $this->toDocument($hit),
            $this->client->search($this->indexUid, $embedding, $k, $this->embedder)
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MissingParameterException
     */
    private function toMeilisearchDocument(Document $document): array
    {
        if ($document->embedding === null) {
            throw new MissingParameterException('It is impossible to save a document without its vectors. You need to call an embeddingGenerator: $embededDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);');
        }

        return [
            'id' => $this->getId($document),
            'content' => $document->content,
            'formattedContent' => $document->formattedContent,
            'sourceType' => $document->sourceType,
            'sourceName' => $document->sourceName,
            'hash' => $document->hash,
            'chunkNumber' => $document->chunkNumber,
            'metadata' => $document->metadata,
            '_vectors' => [
                $this->embedder => [
                    'embeddings' => $document->embedding,
                    'regenerate' => false,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $hit
     */
    private function toDocument(array $hit): Document
    {
        $document = new Document();
        $document->id = $hit['id'] ?? null;
        $document->content = $this->readString($hit, 'content');
        $formattedContent = $hit['formattedContent'] ?? null;
        $document->formattedContent = is_string($formattedContent) ? $formattedContent : null;
        $document->sourceType = $this->readString($hit, 'sourceType', $document->sourceType);
        $document->sourceName = $this->readString($hit, 'sourceName', $document->sourceName);
        $document->hash = $this->readString($hit, 'hash');
        $chunkNumber = $hit['chunkNumber'] ?? null;
        $document->chunkNumber = is_int($chunkNumber) ? $chunkNumber : 0;
        $document->metadata = $this->readMetadata($hit);
        $document->embedding = $this->readEmbedding($hit);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $hit
     */
    private function readString(array $hit, string $name, string $default = ''): string
    {
        $value = $hit[$name] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>
     */
    private function readMetadata(array $hit): array
    {
        $rawMetadata = $hit['metadata'] ?? null;
        if (! is_array($rawMetadata)) {
            return [];
        }

        $metadata = [];
        foreach ($rawMetadata as $name => $value) {
            if (is_string($name)) {
                $metadata[$name] = $value;
            }
        }

        return $metadata;
    }

    /**
     * The hit carries its vector only because the search asks for it; Meilisearch needs one back to
     * rebuild a Document, so a hit without it keeps the field empty.
     *
     * @param  array<string, mixed>  $hit
     * @return float[]|null
     */
    private function readEmbedding(array $hit): ?array
    {
        $vectors = $hit['_vectors'] ?? null;
        if (! is_array($vectors)) {
            return null;
        }

        $vector = $vectors[$this->embedder] ?? null;
        if (! is_array($vector)) {
            return null;
        }

        // The stored form carries an ``embeddings`` list, one entry per vector; a plain list of
        // numbers is the shorter form Meilisearch also accepts.
        $rawEmbeddings = $vector['embeddings'] ?? $vector;
        if (! is_array($rawEmbeddings)) {
            return null;
        }

        // Meilisearch returns a list of vectors per embedder; this store writes exactly one.
        if ($rawEmbeddings !== [] && \array_is_list($rawEmbeddings) && is_array($rawEmbeddings[0])) {
            $rawEmbeddings = $rawEmbeddings[0];
        }

        $embedding = [];
        foreach ($rawEmbeddings as $value) {
            if (is_int($value) || is_float($value)) {
                $embedding[] = (float) $value;
            }
        }

        return $embedding === [] ? null : $embedding;
    }

    protected function getId(Document $document): string
    {
        return hash('sha256', $document->content.DocumentUtils::getUniqueId($document));
    }
}
