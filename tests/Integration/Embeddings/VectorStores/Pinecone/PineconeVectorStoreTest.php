<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\VectorStores\Pinecone;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Pinecone\PineconeClient;
use LLPhant\Embeddings\VectorStores\Pinecone\PineconeVectorStore;
use LLPhant\OllamaConfig;
use Tests\Integration\Embeddings\VectorStores\Doctrine\PlaceEntity;

/**
 * Builds a PineconeVectorStore ready for testing.
 *
 * The local pinecone-local server exposes both the control and data APIs on the
 * same host (PINECONE_HOST).  createIndex() returns the data-plane host from the
 * Pinecone response; for local containers that host may use "localhost" as the
 * hostname, which is unreachable from inside Docker.  We therefore replace the
 * authority portion of the returned URL with the one from PINECONE_HOST so that
 * connections from the PHP container always use the correct hostname/port.
 */
function getPineconeVectorStore(EmbeddingGeneratorInterface $embeddingGenerator): PineconeVectorStore
{
    $controlHost = getenv('PINECONE_HOST') ?: 'http://localhost:5080';
    $indexName = getenv('PINECONE_INDEX_NAME') ?: 'llphant-test';
    $apiKey = getenv('PINECONE_API_KEY') ?: '';

    $dimension = $embeddingGenerator->getEmbeddingLength();

    $setupClient = new PineconeClient(host: $controlHost, apiKey: $apiKey);
    $returnedHost = $setupClient->createIndex($indexName, $dimension);

    // The returned host from the local Pinecone server may contain "localhost" as
    // the hostname, which is not reachable from within a Docker container.  We
    // preserve only the port from the response and replace the scheme+host
    // with the authority already known to be reachable via PINECONE_HOST.
    $scheme = parse_url($controlHost, PHP_URL_SCHEME) ?? 'http';
    $configuredHost = parse_url($controlHost, PHP_URL_HOST) ?? 'localhost';
    $returnedPort = parse_url('http://'.$returnedHost, PHP_URL_PORT);

    $dataHost = $returnedPort !== null
        ? $scheme.'://'.$configuredHost.':'.$returnedPort
        : $controlHost;

    $client = new PineconeClient(host: $dataHost, apiKey: $apiKey);

    return new PineconeVectorStore($client, PineconeVectorStore::DEFAULT_NAMESPACE, $dimension);
}

function deletePineconeIndex(): void
{
    $controlHost = getenv('PINECONE_HOST') ?: 'http://localhost:5080';
    $indexName = getenv('PINECONE_INDEX_NAME') ?: 'llphant-test';
    $apiKey = getenv('PINECONE_API_KEY') ?: '';

    $client = new PineconeClient(host: $controlHost, apiKey: $apiKey);
    $client->deleteIndex($indexName);

    // Give the local server a moment to fully remove the index before
    // the next test tries to create it again.
    sleep(2);
}

function createEmbeddingGenerator(): EmbeddingGeneratorInterface
{
    $config = new OllamaConfig();
    $config->model = 'nomic-embed-text';
    $config->url = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/';

    return new OllamaEmbeddingGenerator($config);
}

beforeEach(function () {
    deletePineconeIndex();
});

it('creates documents with their embeddings and performs a similarity search', function () {
    $embeddingGenerator = createEmbeddingGenerator();
    $vectorStore = getPineconeVectorStore($embeddingGenerator);

    $docs = DocumentUtils::documents(
        'Anna reads Dante',
        'I love carbonara',
        'Do not put pineapples on pizza',
        'New York is in the USA',
        'My cat is black',
        'Anna lives in Rome'
    );

    $embeddedDocuments = $embeddingGenerator->embedDocuments($docs);
    $vectorStore->addDocuments($embeddedDocuments);

    // Pinecone indexing may take a moment to become consistent
    sleep(5);

    $embedding = $embeddingGenerator->embedText('Anna lives in Italy');
    $result = $vectorStore->similaritySearch($embedding, 2);

    expect($result[0]->content)->toBe('Anna lives in Rome');
});

it('tests a full embedding flow with Pinecone', function () {
    $embeddingGenerator = createEmbeddingGenerator();
    $vectorStore = getPineconeVectorStore($embeddingGenerator);

    $filePath = __DIR__.'/../PlacesTextFiles';
    $reader = new FileDataReader($filePath, PlaceEntity::class);
    $documents = $reader->getDocuments();
    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 100, "\n");
    $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splittedDocuments);

    $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);

    $vectorStore->addDocuments($embeddedDocuments);

    sleep(5);

    $embedding = $embeddingGenerator->embedText('France the country');
    /** @var PlaceEntity[] $result */
    $result = $vectorStore->similaritySearch($embedding, 2);

    expect(explode(' ', $result[0]->content)[0])->toBe('France');
});

it('fetches documents by chunk range', function () {
    $embeddingGenerator = createEmbeddingGenerator();
    $vectorStore = getPineconeVectorStore($embeddingGenerator);

    $filePath = __DIR__.'/../PlacesTextFiles';
    $reader = new FileDataReader($filePath, PlaceEntity::class);
    $documents = $reader->getDocuments();
    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 100, "\n");
    $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splittedDocuments);

    $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);

    $vectorStore->addDocuments($embeddedDocuments);

    sleep(5);

    // Retrieve the source type and name from one of the embedded documents to use in the range query
    $sourceType = $embeddedDocuments[0]->sourceType;
    $sourceName = $embeddedDocuments[0]->sourceName;

    $chunksInSource = array_values(array_filter(
        $embeddedDocuments,
        fn ($doc) => $doc->sourceType === $sourceType && $doc->sourceName === $sourceName
    ));

    $maxChunk = max(array_map(fn ($doc) => $doc->chunkNumber, $chunksInSource));

    $result = $vectorStore->fetchDocumentsByChunkRange($sourceType, $sourceName, 0, $maxChunk);
    $resultArray = [...$result];

    expect($resultArray)->not->toBeEmpty();

    // Verify that results are ordered by chunkNumber
    for ($i = 1; $i < count($resultArray); $i++) {
        expect($resultArray[$i]->chunkNumber)->toBeGreaterThanOrEqual($resultArray[$i - 1]->chunkNumber);
    }

    // Verify source metadata is correct
    foreach ($resultArray as $doc) {
        expect($doc->sourceType)->toBe($sourceType);
        expect($doc->sourceName)->toBe($sourceName);
    }
});
