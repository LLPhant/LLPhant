<?php

namespace LLPhant\Embeddings\VectorStores\Redis;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use Predis\Client;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Response\ServerException;

class RedisVectorStore extends VectorStoreBase
{
    final public const LLPHANT_INDEX = 'llphant';

    public function __construct(public Client $client, public string $redisIndex = self::LLPHANT_INDEX)
    {
    }

    public function addDocument(Document $document): void
    {
        $this->client->jsonmset(...$this->generateRedisJsonSetArguments($document));
    }

    /**
     * @param  Document[]  $documents
     */
    public function addDocuments(array $documents, int $numberOfDocumentsPerRequest = 0): void
    {
        if ($documents === []) {
            return;
        }

        if ($numberOfDocumentsPerRequest === 0) {
            $numberOfDocumentsPerRequest = count($documents);
        }

        $redisArgs = [];
        $documentCounter = 1;
        foreach ($documents as $document) {
            array_push($redisArgs, ...$this->generateRedisJsonSetArguments($document));
            if ($documentCounter % $numberOfDocumentsPerRequest === 0) {
                $this->client->jsonmset(...$redisArgs);
                $redisArgs = [];
            }
            $documentCounter++;
        }

        if ($redisArgs !== []) {
            $this->client->jsonmset(...$redisArgs);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param  array{filters?: string}  $additionalArguments
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $vectorDimension = count($embedding);
        $this->createIndexIfMissing($vectorDimension);

        $binaryQueryVector = '';
        foreach ($embedding as $value) {
            $binaryQueryVector .= pack('f', $value);
        }

        $filter = array_key_exists('filters', $additionalArguments) ?
            $additionalArguments['filters']
            : '*';

        /** @var array{0: int, 1: string, 2: string[]} $rawResults */
        $rawResults = $this->client->ftsearch(
            $this->redisIndex,
            "($filter)=>[KNN $k @embedding \$query_vector AS distance]",
            (new SearchArguments())
                ->dialect('2')
                ->params(['query_vector', $binaryQueryVector])
                ->sortBy('distance', 'ASC')
        );

        return $this->getDocumentsFromRedisResult($rawResults);
    }

    /**
     * @param  array{0: int, 1: string, 2: string[]}  $rawRedisResults
     * @return Document[]
     */
    private function getDocumentsFromRedisResult(array $rawRedisResults): array
    {
        /*
        ### Example of a rawRedisResults ###
            0 => 3                              --> number of results
            1 => "llphant:files:france.txt:0"   --> ($i) redis document id
            2 => array:4 [                      --> ($i + 1)
                0 => "distance"
                1 => "0.121247768402"           --> ($i + 1)[1] distance from the query vector
                2 => "$"
                3 => "{'content':'Fran...       --> ($i + 1)[3] json encoded document
            3 => "llphant:files:paris.txt:0"    --> ($i + 2) = $i on the 2nd iteration of the loop
            4 => array:4 [
                0 => "distance"
                ...
        */

        $documents = [];
        $rawRedisResultsCount = count($rawRedisResults);
        for ($i = 1; $i < $rawRedisResultsCount; $i += 2) {
            [$distanceLabel, $distanceValue, $redisPath, $jsonEncodedDocument] = $rawRedisResults[$i + 1];
            /** @var array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int} $data */
            $data = json_decode($jsonEncodedDocument, true, 512, JSON_THROW_ON_ERROR);
            $documents[] = DocumentUtils::createDocumentFromArray($data);
        }

        return $documents;
    }

    private function createIndexIfMissing(int $vectorDimension): void
    {
        try {
            $this->client->ftinfo($this->redisIndex);
        } catch (ServerException $e) {
            if (! $this->isMissingIndexError($e)) {
                throw $e;
            }
            $this->createIndex($vectorDimension);
        }
    }

    private function createIndex(int $vectorDimension): void
    {
        $schema = [
            new TextField('$.content', 'content'),
            new TextField('$.formattedContent', 'formattedContent'),
            new TextField('$.sourceType', 'sourceType'),
            new TextField('$.sourceName', 'sourceName'),
            new TextField('$.hash', 'hash'),
            new VectorField('$.embedding', 'FLAT', [
                'DIM', $vectorDimension,
                'TYPE', 'FLOAT32',
                'DISTANCE_METRIC', 'COSINE',
            ], 'embedding'),
        ];

        $this->client->ftcreate($this->redisIndex, $schema,
            (new CreateArguments())
                ->on('JSON')
                ->prefix([$this->redisIndex.':'])
        );
    }

    /**
     * @return string[]
     */
    private function generateRedisJsonSetArguments(Document $document): array
    {
        return [
            $this->redisIndex.':'.DocumentUtils::getUniqueId($document),
            '$',
            json_encode($document, JSON_THROW_ON_ERROR),
        ];
    }

    private function isMissingIndexError(\Exception|ServerException $e): bool
    {
        return ($e->getMessage() == 'Unknown index name') || (str_ends_with($e->getMessage(), 'no such index'));
    }
}
