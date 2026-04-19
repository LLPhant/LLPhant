<?php

namespace Tests\Unit\Embeddings\VectorStores\Qdrant;

use LLPhant\Embeddings\VectorStores\Qdrant\QdrantVectorStore;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Qdrant\Config;
use Qdrant\Endpoints\Collections;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Qdrant;

it('can create collection', function () {
    $fake = FakeQdrant::create(FakeQdrant::QDRANT_COLLECTION_LIST);
    $qdrantStore = $fake->qdrantStore;
    $history = $fake->history;
    $qdrantStore->createCollection('oneCollection', 512);
    $content = $history[0]['request']->getBody()->getContents();
    expect($content)->toBe('{"vectors":{"openai":{"size":512,"distance":"Cosine"}}}');
});

it('can create collection with null vectorName', function () {
    $fake = FakeQdrant::create(FakeQdrant::QDRANT_COLLECTION_LIST);
    $qdrantStore = $fake->qdrantStore;
    $history = $fake->history;
    $qdrantStore->setVectorName(null);
    $qdrantStore->createCollection('oneCollection', 512);
    $content = $history[0]['request']->getBody()->getContents();
    expect($content)->toBe('{"vectors":{"size":512,"distance":"Cosine"}}');
});

it('can set distance', function () {
    $fake = FakeQdrant::create(FakeQdrant::QDRANT_COLLECTION_LIST);
    $qdrantStore = $fake->qdrantStore;
    $history = $fake->history;
    $qdrantStore->setDistance(VectorParams::DISTANCE_EUCLID);
    $qdrantStore->createCollection('oneCollection', 512);
    $content = $history[0]['request']->getBody()->getContents();
    expect($content)->toBe('{"vectors":{"openai":{"size":512,"distance":"Euclid"}}}');
});

it('can perform similarity search', function () {
    $fakeEmbedding = [];
    $qdrantStore = FakeQdrant::create(FakeQdrant::QDRANT_COLLECTION_LIST)->qdrantStore;
    $response = $qdrantStore->similaritySearch($fakeEmbedding, 2);
    expect($response)->toHaveCount(2)
        ->and($response[0]->content)->toStartWith('France')
        ->and($response[0]->id)->toBe('c4ff4e3f62b63f67f34d3e64e7c53ca5f12dba0035bd471eae8f2ef0f5689432')
        ->and($response[1]->content)->toBe('The house is on fire');
});

it('can delete a collection successfully', function () {
    $collectionName = 'test_collection';

    $mockPsrResponse = Mockery::mock(ResponseInterface::class);
    $mockPsrResponse->shouldReceive('getHeaderLine')->with('content-type')->andReturn('application/json');
    $mockPsrResponse->shouldReceive('getBody')->andReturn(Mockery::mock(\Psr\Http\Message\StreamInterface::class, ['getContents' => '{"result":true}']));

    $mockCollectionClient = Mockery::mock(Collections::class);
    $mockCollectionClient->shouldReceive('delete')->once()->andReturn(new \Qdrant\Response($mockPsrResponse)); // Simulate success

    $mockQdrantClient = Mockery::mock(Qdrant::class);
    $mockQdrantClient->shouldReceive('collections')
        ->with($collectionName)
        ->once()
        ->andReturn($mockCollectionClient);

    // Create an instance of QdrantVectorStore and inject the mock client
    $config = new Config('host', 1111);
    $qdrantStore = new QdrantVectorStore($config, $collectionName);
    $qdrantStore->setClient($mockQdrantClient);

    // Call the method under test
    $qdrantStore->deleteCollection($collectionName);

    // Assertions are handled by Mockery's shouldReceive->once()
    expect(true)->toBeTrue(); // Dummy assertion to satisfy Pest, Mockery does the heavy lifting
});

it('handles deleting a non-existent collection gracefully', function () {
    $collectionName = 'non_existent_collection';

    // Mock the collections client to throw an exception when delete is called
    $mockCollectionClient = Mockery::mock(Collections::class);
    $mockCollectionClient->shouldReceive('delete')->once()->andThrow(new \Exception('Collection not found')); // Simulate error

    $mockQdrantClient = Mockery::mock(Qdrant::class);
    $mockQdrantClient->shouldReceive('collections')
        ->with($collectionName)
        ->once()
        ->andReturn($mockCollectionClient);

    // Create an instance of QdrantVectorStore and inject the mock client
    $config = new Config('host', 1111);
    $qdrantStore = new QdrantVectorStore($config, $collectionName);
    $qdrantStore->setClient($mockQdrantClient);

    // Call the method under test, expecting no exception to be thrown
    $qdrantStore->deleteCollection($collectionName);

    // If we reach here, it means the exception was caught gracefully
    expect(true)->toBeTrue();
});
