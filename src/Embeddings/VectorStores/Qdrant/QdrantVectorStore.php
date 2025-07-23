<?php

namespace LLPhant\Embeddings\VectorStores\Qdrant;

use Exception;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use Qdrant\Config;
use Qdrant\Exception\InvalidArgumentException;
use Qdrant\Http\Transport;
use Qdrant\Models\Filter\Condition\ConditionInterface;
use Qdrant\Models\Filter\Filter;
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

    public function __construct(
        Config $config,
        private string $collectionName,
        private ?string $vectorName = self::QDRANT_OPENAI_VECTOR_NAME,
        private string $distance = VectorParams::DISTANCE_COSINE,
    ) {
        $this->client = new Qdrant(new Transport(Psr18ClientDiscovery::find(), $config));
    }

    public function setClient(Qdrant $client): void
    {
        $this->client = $client;
    }

    public function setVectorName(?string $vectorName): void
    {
        $this->vectorName = $vectorName;
    }

    /**
     * @param  $distance  string [Cosine, Euclid, Dot]
     *
     * @throws InvalidArgumentException
     */
    public function setDistance(string $distance): void
    {
        if (! in_array($distance, [VectorParams::DISTANCE_COSINE, VectorParams::DISTANCE_DOT, VectorParams::DISTANCE_EUCLID])) {
            throw new InvalidArgumentException('Invalid distance');
        }
        $this->distance = $distance;
    }

    /**
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollectionIfDoesNotExist(string $name, int $embeddingLength): bool
    {
        try {
            $this->client->collections($name)->info();

            return true;
        } catch (\Exception) {
            $this->createCollection($name, $embeddingLength);

            return false;
        }
    }

    /**
     * @param  int  $embeddingLength  this depends on the embedding generator you use
     */
    public function createCollection(string $name, int $embeddingLength): Response
    {
        $createCollection = new CreateCollection();
        $vectorParams = new VectorParams($embeddingLength, $this->distance);
        $createCollection->addVector($vectorParams, $this->vectorName);
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

    /**
     * @param  float[]  $embedding
     * @param  array<string, ConditionInterface[]>  $additionalArguments
     * @return array<int, Document>
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $vectorStruct = new VectorStruct($embedding, $this->vectorName);
        $filter = new Filter();

        if (isset($additionalArguments['must'])) {
            foreach ($additionalArguments['must'] as $condition) {
                $filter->addMust($condition);
            }
        }

        if (isset($additionalArguments['must_not'])) {
            foreach ($additionalArguments['must_not'] as $condition) {
                $filter->addMustNot($condition);
            }
        }

        if (isset($additionalArguments['should'])) {
            foreach ($additionalArguments['should'] as $condition) {
                $filter->addShould($condition);
            }
        }

        $searchRequest = (new SearchRequest($vectorStruct))
            ->setFilter($filter)
            ->setLimit($k)
            ->setParams([
                'hnsw_ef' => 128,
                'exact' => true,
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
            $document->id = $onePoint['id'];
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

        $id = DocumentUtils::formatUUIDFromUniqueId(DocumentUtils::getUniqueId($document));
        $points->addPoint(
            new PointStruct(
                $id,
                new VectorStruct($document->embedding, $this->vectorName),
                [
                    'id' => $id,
                    'content' => $document->content,
                    'hash' => $document->hash,
                    'sourceName' => $document->sourceName,
                    'sourceType' => $document->sourceType,
                ]
            )
        );
    }
}
