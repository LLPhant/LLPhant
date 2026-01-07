<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\VectorStores\MongoDB;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\MongoDB\MongoDBVectorStore;
use MongoDB\Client;
use Tests\Integration\Embeddings\VectorStores\Doctrine\PlaceEntity;

const MONGODB_TEST_DB = 'llphant_test_db';

beforeAll(function () {
    $client = new Client(getenv('MONGODB_CONNECTION_STRING'));
    $client->selectDatabase(MONGODB_TEST_DB)->drop();
});

it('creates two documents with their embeddings and performs a similarity search', function () {
    $docs = DocumentUtils::documents(
        'Anna reads Dante',
        'I love carbonara',
        'Do not put pineapples on pizza',
        'New York is in the USA',
        'My cat is black',
        'Anna lives in Rome'
    );

    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $embeddedDocuments = $embeddingGenerator->embedDocuments($docs);

    $client = new Client(getenv('MONGODB_CONNECTION_STRING'));
    $vectorStore = new MongoDBVectorStore($client, database: MONGODB_TEST_DB, collection: 'test_phrases');
    $vectorStore->addDocuments($embeddedDocuments);

    $embedding = $embeddingGenerator->embedText('Anna lives in Italy');
    $result = $vectorStore->similaritySearch($embedding, 2);

    // We check that the search returns the correct documents in the right order
    expect($result[0]->content)->toBe('Anna lives in Rome');
});

it('tests a full embedding flow with MongoDB', function () {
    $filePath = __DIR__.'/../PlacesTextFiles';
    $reader = new FileDataReader($filePath, PlaceEntity::class);
    $documents = $reader->getDocuments();
    $splitDocuments = DocumentSplitter::splitDocuments($documents, 100, "\n");
    $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splitDocuments);

    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);

    $client = new Client(getenv('MONGODB_CONNECTION_STRING'));
    $vectorStore = new MongoDBVectorStore($client, database: MONGODB_TEST_DB, collection: 'test_places');
    $vectorStore->addDocuments($embeddedDocuments);

    $embedding = $embeddingGenerator->embedText('France the country');
    /** @var PlaceEntity[] $result */
    $result = $vectorStore->similaritySearch($embedding, 2);

    // We check that the search returns the correct entities in the right order
    expect(explode(' ', $result[0]->content)[0])->toBe('France');
});
