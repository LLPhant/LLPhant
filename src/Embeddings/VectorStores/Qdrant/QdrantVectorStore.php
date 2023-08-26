<?php

namespace LLPhant\Embeddings\VectorStores\Qdrant;

use Exception;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAIEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use Qdrant\Config;
use Qdrant\Http\GuzzleClient;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Request\CreateCollection;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Qdrant\Response;

class QdrantVectorStore extends VectorStoreBase
{
    final public const QDRANT_OPENAI_VECTOR_NAME = 'openai';

    public Qdrant $client;

    public function __construct(Config $config, private string $collectionName)
    {
        $this->client = new Qdrant(new GuzzleClient($config));
    }

    public function createCollection(string $name): Response
    {
        $createCollection = new CreateCollection();

        $createCollection->addVector(
            new VectorParams(
                OpenAIEmbeddingGenerator::OPENAI_EMBEDDING_LENGTH,
                VectorParams::DISTANCE_COSINE), QdrantVectorStore::QDRANT_OPENAI_VECTOR_NAME);
        $response = $this->client->collections($name)->create($createCollection);
        $this->collectionName = $name;

        return $response;
    }

    public function addDocument(Document $document): void
    {
        $points = new PointsStruct();
        $this->createPointFromDocument($points, $document);
        $this->client->collections($this->collectionName)->points()->upsert($points);
    }

    public function addDocuments(array $documents): void
    {
        $points = new PointsStruct();

        if ($documents === []) {
            return;
        }

        foreach ($documents as $document) {
            $this->createPointFromDocument($points, $document);
        }

        $this->client->collections($this->collectionName)->points()->upsert($points);
    }

    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $vectorStruct = new VectorStruct($embedding, QdrantVectorStore::QDRANT_OPENAI_VECTOR_NAME);
        $searchRequest = (new SearchRequest($vectorStruct))
            ->setLimit($k)
            ->setParams([
                'hnsw_ef' => 128,
                'exact' => false,
            ])
            ->setWithPayload(true);

        $response = $this->client->collections($this->collectionName)->points()->search($searchRequest);
        $arrayResponse = $response->__toArray();
        $results = $arrayResponse['result'];

        if ((is_countable($results) ? count($results) : 0) === 0) {
            return [];
        }

        $documents = [];
        foreach ($results as $onePoint) {
            $document = new Document();
            $document->content = $onePoint['payload']['content'];
            $document->hash = $onePoint['payload']['hash'];
            $document->sourceType = $onePoint['payload']['sourceType'];
            $document->sourceName = $onePoint['payload']['sourceName'];
            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * @throws Exception
     */
    private function createPointFromDocument(PointsStruct $points, Document $document): void
    {
        if (! is_array($document->embedding)) {
            throw new Exception('Impossible to save a document without its vectors. You need to call an embeddingGenerator: $embededDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);');
        }

        $points->addPoint(
            new PointStruct(
                DocumentUtils::getUniqueId($document),
                new VectorStruct($document->embedding, QdrantVectorStore::QDRANT_OPENAI_VECTOR_NAME),
                [
                    'id' => $document->hash,
                    'content' => $document->content,
                    'hash' => $document->hash,
                    'sourceName' => $document->sourceName,
                    'sourceType' => $document->sourceType,
                ]
            )
        );
    }
}
