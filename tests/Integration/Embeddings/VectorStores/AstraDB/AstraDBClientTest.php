<?php

declare(strict_types=1);

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\AstraDB\AstraDBClient;

it('can create and delete a collection', function () {
    $client = new AstraDBClient(endpoint: '', token: '', collectionName: 'test_collection');

    $client->deleteCollection();

    expect($client->collectionVectorDimension())->toBe(false);

    $client->createCollection();

    expect($client->collectionVectorDimension())->toBe(true);

    $client->deleteCollection();

    expect($client->collectionVectorDimension())->toBe(false);
});

it('can insert documents', function () {
    $client = new AstraDBClient(endpoint: '', token: '');

    if (! $client->collectionVectorDimension()) {
        $client->createCollection();
    }

    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();

    $documents = [
        [
            '_id' => \microtime(),
            '$vector' => $embeddingGenerator->embedText('My cat is black'),
        ],
        [
            '_id' => \microtime(),
            '$vector' => $embeddingGenerator->embedText('My dog is white'),
        ],
    ];

    $insertedIds = $client->insertData($documents);
    expect($insertedIds[0])->toBe($documents[0]['_id'])
        ->and($insertedIds[1])->toBe($documents[1]['_id']);
});
