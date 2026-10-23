<?php

declare(strict_types=1);

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Meilisearch\MeilisearchVectorStore;
use Meilisearch\Client;

it('creates, upserts, searches and deletes documents in Meilisearch', function () {
    $client = new Client(getenv('MEILISEARCH_URL') ?: 'http://localhost:7700', getenv('MEILISEARCH_API_KEY') ?: null);
    $indexName = 'llphant_test_'.bin2hex(random_bytes(4));
    $store = new MeilisearchVectorStore($client, $indexName, 'custom');
    $store->createIndex(3);

    try {
        $task = $client->index($indexName)->updateFilterableAttributes(['metadata.country']);
        $client->waitForTask($task['taskUid']);

        $documents = DocumentUtils::documents('Paris is in France', 'Rome is in Italy');
        $documents[0]->embedding = [1.0, 0.0, 0.0];
        $documents[0]->metadata = ['country' => 'France'];
        $documents[1]->embedding = [0.0, 1.0, 0.0];
        $store->addDocuments($documents);
        $store->addDocument($documents[0]);

        $results = $store->similaritySearch([1.0, 0.0, 0.0], 2);
        expect($results)->toHaveCount(2)
            ->and($results[0]->content)->toBe($documents[0]->content)
            ->and($results[0]->metadata)->toBe(['country' => 'France']);

        $filtered = $store->similaritySearch([1.0, 0.0, 0.0], 2, ['filter' => 'metadata.country = France']);
        expect($filtered)->toHaveCount(1)
            ->and($filtered[0]->content)->toBe($documents[0]->content);

        $store->deleteDocument($results[0]);
        expect($store->similaritySearch([1.0, 0.0, 0.0], 2))->toHaveCount(1);
        $store->deleteDocuments([$documents[1]]);
        expect($store->similaritySearch([1.0, 0.0, 0.0]))->toBe([]);
    } finally {
        $store->deleteIndex();
    }
});
