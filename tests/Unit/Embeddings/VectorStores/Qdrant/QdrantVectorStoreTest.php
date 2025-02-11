<?php

namespace Tests\Unit\Embeddings\VectorStores\Qdrant;

use Qdrant\Models\Request\VectorParams;

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
