<?php

declare(strict_types=1);

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Pinecone\PineconeClient;
use LLPhant\Embeddings\VectorStores\Pinecone\PineconeVectorStore;
use LLPhant\Exception\SecurityException;
use Tests\Fixtures\DocumentFixtures;

it('can add documents and maps them to the expected Pinecone vector structure', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('upsert')
        ->once()
        ->with(
            Mockery::on(function (array $vectors): bool {
                if (count($vectors) !== 1) {
                    return false;
                }
                $v = $vectors[0];

                return $v['id'] === $v['metadata']['hash']
                    && $v['values'] === [0.1, 0.2]
                    && $v['metadata']['content'] === 'Document 0'
                    && $v['metadata']['sourceType'] === 'aType'
                    && $v['metadata']['sourceName'] === 'aName'
                    && $v['metadata']['chunkNumber'] === 0;
            }),
            ''
        )
        ->andReturn(true);

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->addDocuments([DocumentFixtures::documentChunk(0, 'aType', 'aName')]);
});

it('throws when upsert fails', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('upsert')->andReturn(false);

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->addDocuments([DocumentFixtures::documentChunk(0, 'aType', 'aName')]);
})->throws(\Exception::class, 'Failed to upsert vectors to Pinecone.');

it('skips upsert when documents array is empty', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldNotReceive('upsert');

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->addDocuments([]);
});

it('can perform similarity search and maps matches to documents', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('query')
        ->once()
        ->andReturn([
            [
                'id' => 'abc123',
                'metadata' => [
                    'content' => 'Document 1',
                    'formattedContent' => 'Document 1 formatted',
                    'sourceType' => 'aType',
                    'sourceName' => 'aName',
                    'chunkNumber' => 1,
                ],
            ],
            [
                'id' => 'def456',
                'metadata' => [
                    'content' => 'Document 2',
                    'formattedContent' => 'Document 2 formatted',
                    'sourceType' => 'aType',
                    'sourceName' => 'aName',
                    'chunkNumber' => 2,
                ],
            ],
        ]);

    $vectorStore = new PineconeVectorStore($client);
    $results = $vectorStore->similaritySearch([0.1, 0.2], 2);

    expect($results)->toHaveCount(2)
        ->and($results[0]->content)->toBe('Document 1')
        ->and($results[0]->hash)->toBe('abc123')
        ->and($results[0]->chunkNumber)->toBe(1)
        ->and($results[1]->content)->toBe('Document 2');
});

it('passes filter from additionalArguments to query', function () {
    $filter = ['sourceType' => ['$eq' => 'aType']];

    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('query')
        ->once()
        ->with([0.1, 0.2], 4, $filter, '')
        ->andReturn([]);

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->similaritySearch([0.1, 0.2], 4, ['filter' => $filter]);
});

it('can detect empty sourceType in fetchDocumentsByChunkRange', function (string $input) {
    $vectorStore = new PineconeVectorStore(Mockery::mock(PineconeClient::class));
    $vectorStore->fetchDocumentsByChunkRange($input, 'aName', 0, 2);
})->with(['', '   '])->throws(SecurityException::class, 'Invalid source type or name');

it('can detect empty sourceName in fetchDocumentsByChunkRange', function (string $input) {
    $vectorStore = new PineconeVectorStore(Mockery::mock(PineconeClient::class));
    $vectorStore->fetchDocumentsByChunkRange('aType', $input, 0, 2);
})->with(['', '   '])->throws(SecurityException::class, 'Invalid source type or name');

it('can fetch documents by chunk range and returns them sorted by chunkNumber', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('describeIndexStats')->andReturn(['dimension' => 2]);
    $client->shouldReceive('query')
        ->once()
        ->with(
            [0.0, 0.0],
            3,
            [
                'sourceType' => ['$eq' => 'aType'],
                'sourceName' => ['$eq' => 'aName'],
                'chunkNumber' => ['$gte' => 0, '$lte' => 2],
            ],
            ''
        )
        ->andReturn([
            ['id' => 'c', 'metadata' => ['content' => 'Document 2', 'formattedContent' => '', 'sourceType' => 'aType', 'sourceName' => 'aName', 'chunkNumber' => 2]],
            ['id' => 'a', 'metadata' => ['content' => 'Document 0', 'formattedContent' => '', 'sourceType' => 'aType', 'sourceName' => 'aName', 'chunkNumber' => 0]],
            ['id' => 'b', 'metadata' => ['content' => 'Document 1', 'formattedContent' => '', 'sourceType' => 'aType', 'sourceName' => 'aName', 'chunkNumber' => 1]],
        ]);

    $vectorStore = new PineconeVectorStore($client);
    $documents = $vectorStore->fetchDocumentsByChunkRange('aType', 'aName', 0, 2);

    expect($documents)->toHaveCount(3)
        ->and($documents[0]->chunkNumber)->toBe(0)
        ->and($documents[1]->chunkNumber)->toBe(1)
        ->and($documents[2]->chunkNumber)->toBe(2);
});

it('uses topK of rightIndex - leftIndex + 1 when fetching by chunk range', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('describeIndexStats')->andReturn(['dimension' => 2]);
    $client->shouldReceive('query')
        ->once()
        ->with(Mockery::any(), 6, Mockery::any(), '')
        ->andReturn([]);

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->fetchDocumentsByChunkRange('aType', 'aName', 2, 7);
});

it('falls back to dimension 1536 when describeIndexStats has no dimension', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('describeIndexStats')->andReturn([]);
    $client->shouldReceive('query')
        ->once()
        ->with(array_fill(0, 1536, 0.0), Mockery::any(), Mockery::any(), '')
        ->andReturn([]);

    $vectorStore = new PineconeVectorStore($client);
    $vectorStore->fetchDocumentsByChunkRange('aType', 'aName', 0, 2);
});

it('passes namespace to client calls', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('upsert')
        ->once()
        ->with(Mockery::any(), 'my-namespace')
        ->andReturn(true);

    $vectorStore = new PineconeVectorStore($client, 'my-namespace');
    $vectorStore->addDocuments([DocumentFixtures::documentChunk(0, 'aType', 'aName')]);
});

it('maps document unique id correctly from fetched chunk range', function () {
    $client = Mockery::mock(PineconeClient::class);
    $client->shouldReceive('describeIndexStats')->andReturn(['dimension' => 2]);
    $client->shouldReceive('query')->andReturn([
        ['id' => 'hash1', 'metadata' => ['content' => 'Document 1', 'formattedContent' => '', 'sourceType' => 'aType', 'sourceName' => 'aName', 'chunkNumber' => 1]],
    ]);

    $vectorStore = new PineconeVectorStore($client);
    $documents = $vectorStore->fetchDocumentsByChunkRange('aType', 'aName', 0, 2);

    expect($documents)->toHaveCount(1)
        ->and(DocumentUtils::getUniqueId($documents[0]))->toBe('aType:aName:1');
});
