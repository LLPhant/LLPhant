<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\VectorStores\Redis;

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use Predis\Client;

it('tests a full embedding flow with Redis', function () {
    // Get the already embeded france.txt and paris.txt documents
    $path = __DIR__.'/../EmbeddedMock/francetxt_paristxt.json';
    $rawFileContent = file_get_contents($path);
    if (! $rawFileContent) {
        throw new \Exception('File not found');
    }

    $rawDocuments = json_decode($rawFileContent, true);
    $embeddedDocuments = DocumentUtils::createDocumentsFromArray($rawDocuments);

    // Get the embedding of "France the country"
    $path = __DIR__.'/../EmbeddedMock/france_the_country_embedding.json';
    $rawFileContent = file_get_contents($path);
    if (! $rawFileContent) {
        throw new \Exception('File not found');
    }
    /** @var float[] $embeddingQuery */
    $embeddingQuery = json_decode($rawFileContent, true);

    $redisClient = new Client([
        'scheme' => 'tcp',
        'host' => getenv('REDIS_HOST') ?? 'localhost',
        'port' => 6379,
    ]);
    $vectorStore = new RedisVectorStore($redisClient, 'test_idx_'.mt_rand(1, 1000));

    $vectorStore->addDocuments($embeddedDocuments);

    $searchResult1 = $vectorStore->similaritySearch($embeddingQuery, 10);
    expect(DocumentUtils::getFirstWordFromContent($searchResult1[0]))->toBe('France');

    $requestParam = [
        'filters' => '@sourceName:paris.txt',
    ];
    $searchResult2 = $vectorStore->similaritySearch($embeddingQuery, 2, $requestParam);
    expect(DocumentUtils::getFirstWordFromContent($searchResult2[0]))->toBe('Paris');
});
