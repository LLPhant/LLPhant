<?php

namespace LLPhant\Embeddings\VectorStores\Typesense;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use LLPhant\Exception\MissingParameterException;

class TypesenseVectorStore extends VectorStoreBase
{
    final public const TYPESENSE_VECTOR_NAME = 'embedding';

    public function __construct(private readonly string $collectionName, private readonly LLPhantTypesenseClient $client = new LLPhantTypesenseClient(), private readonly string $vectorName = self::TYPESENSE_VECTOR_NAME)
    {
    }

    /**
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollectionIfDoesNotExist(string $name, int $embeddingLength): bool
    {
        if (! $this->collectionExists($name)) {
            $this->createCollection($name, $embeddingLength);

            return false;
        }

        return true;
    }

    /**
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollection(string $name, int $embeddingLength): void
    {
        $this->client->createCollection($name, $embeddingLength, $this->vectorName);
    }

    /**
     * @throws MissingParameterException
     */
    public function addDocument(Document $document): void
    {
        $this->client->upsert($this->collectionName, $this->createPointFromDocument($document));
    }

    /**
     * @throws MissingParameterException
     */
    public function addDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }

    /**
     * @param  float[]  $embedding
     * @return array|mixed[]
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $vectorQuery = $this->vectorName.':(['.implode(',', $embedding).'], k:'.$k.')';

        $response = $this->client->multiSearch([
            'searches' => [
                [
                    'collection' => $this->collectionName,
                    'q' => '*',
                    'vector_query' => $vectorQuery,
                    'exclude_fields' => $this->vectorName,
                ],
            ],
        ]);

        $results = $response['results'];

        if (! \is_array($results)) {
            return [];
        }

        $documents = [];

        foreach ($results as $result) {
            $hits = $result['hits'];
            if (! \is_array($hits)) {
                return $documents;
            }
            foreach ($hits as $onePoint) {
                $document = new Document();
                $document->content = $onePoint['document']['content'];
                $document->hash = $onePoint['document']['hash'];
                $document->sourceType = $onePoint['document']['sourceType'];
                $document->sourceName = $onePoint['document']['sourceName'];
                $document->chunkNumber = $onePoint['document']['chunkNumber'];
                $document->metadata = $onePoint['document']['metadata'] ?? [];
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MissingParameterException
     */
    private function createPointFromDocument(Document $document): array
    {
        if ($document->embedding === null) {
            throw new MissingParameterException('It is impossible to save a document without its vectors. You need to call an embeddingGenerator: $embededDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);');
        }

        return [
            'id' => $this->getId($document),
            $this->vectorName => $document->embedding,
            'content' => $document->content,
            'hash' => $document->hash,
            'sourceName' => $document->sourceName,
            'sourceType' => $document->sourceType,
            'chunkNumber' => $document->chunkNumber,
            'metadata' => $document->metadata,
        ];
    }

    protected function getId(Document $document): string
    {
        return \hash('sha256', $document->content.DocumentUtils::getUniqueId($document));
    }

    public function collectionExists(string $name): bool
    {
        return $this->client->collectionExists($name);
    }
}
