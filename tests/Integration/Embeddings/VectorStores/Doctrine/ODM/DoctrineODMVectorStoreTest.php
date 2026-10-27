<?php

namespace Tests\Integration\Embeddings\VectorStores\Doctrine\ODM;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMEmbeddingEntityBase;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMVectorStore;
use MongoDB\Client;
use Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document\DocumentWithoutVectorField;
use Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document\MultipleVectorFieldsDocument;
use Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document\MultipleVectorIndexesDocument;
use Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document\SampleDocument;
use Tests\Integration\Embeddings\VectorStores\Doctrine\ODM\Document\SingleVectorFieldDocument;

const MONGODB_TEST_DB = 'llphant_test_db';

beforeAll(function () {
    $client = new Client(getenv('MONGODB_CONNECTION_STRING'));
    $client->selectDatabase(MONGODB_TEST_DB)->drop();
});

describe('DoctrineODMVectorStore', function () {
    it('creates three documents with their embeddings and performs a similarity search', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            SingleVectorFieldDocument::class,
        );

        $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();

        $doc1 = new SingleVectorFieldDocument();
        $doc1->content = 'Anna lives in Italy';

        $doc2 = new SingleVectorFieldDocument(published: false);
        $doc2->content = 'Anna lives in the Netherlands';

        $doc3 = new SingleVectorFieldDocument();
        $doc3->content = 'Anna lives in Singapore';

        $embeddedDocuments = $embeddingGenerator->embedDocuments([$doc1, $doc2, $doc3]);
        $vectorStore->addDocuments($embeddedDocuments);

        $embedding = $embeddingGenerator->embedText('Anna lives in Europe');
        $result = $vectorStore->similaritySearch($embedding, 3, ['filter' => ['published' => true]]);

        expect($result[0]->content)
            ->toBe('Anna lives in Italy')
            ->and($result[1]->content)
            ->toBe('Anna lives in Singapore')
            ->and($result)
            ->toHaveCount(2);
    });

    it('it performs a similarity search on an index with multiple vector fields', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            MultipleVectorFieldsDocument::class,
        );

        $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();

        $doc1 = new MultipleVectorFieldsDocument(
            anotherEmbedding: $embeddingGenerator->embedText('Anna likes pizza')
        );
        $doc1->content = 'Anna lives in Italy';

        $doc2 = new MultipleVectorFieldsDocument(
            anotherEmbedding: $embeddingGenerator->embedText('Annemarie likes hiking')
        );
        $doc2->content = 'Annemarie lives in the Netherlands';

        $embeddedDocuments = $embeddingGenerator->embedDocuments([$doc1, $doc2]);
        $vectorStore->addDocuments($embeddedDocuments);

        $embedding = $embeddingGenerator->embedText('I like pasta');
        $result = $vectorStore->similaritySearch($embedding, 1, ['path' => 'anotherEmbedding']);

        expect($result[0]->content)->toBe('Anna lives in Italy');
    });

    it('it performs a similarity search on a collection with multiple vector indexes', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            MultipleVectorIndexesDocument::class,
            'another_index',
        );

        $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();

        $doc1 = new MultipleVectorIndexesDocument(
            anotherEmbedding: $embeddingGenerator->embedText('I like pizza')
        );
        $doc1->content = 'Anna lives in Italy';

        $doc2 = new MultipleVectorIndexesDocument(
            anotherEmbedding: $embeddingGenerator->embedText('I like hiking')
        );
        $doc2->content = 'Annemarie lives in the Netherlands';

        $embeddedDocuments = $embeddingGenerator->embedDocuments([$doc1, $doc2]);
        $vectorStore->addDocuments($embeddedDocuments);

        $embedding = $embeddingGenerator->embedText('I like pasta');
        $result = $vectorStore->similaritySearch($embedding, 1);

        expect($result[0]->content)->toBe('Anna lives in Italy');
    });

    it('can filter documents by chunk number', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            SampleDocument::class,
        );

        $documents = [
            SampleDocument::createDocument('catullo', 'basia', 'Vivamus mea Lesbia, atque amemus,', 0),
            SampleDocument::createDocument('catullo', 'basia', 'rumoresque senum severiorum', 1),
            SampleDocument::createDocument('catullo', 'basia', 'omnes unius aestimemus assis!', 2),

            SampleDocument::createDocument('catullo', 'basia', 'soles occidere et redire possunt:', 3),
            SampleDocument::createDocument('catullo', 'basia', 'nobis cum semel occidit brevis lux,', 4),
            SampleDocument::createDocument('catullo', 'basia', 'nox est perpetua una dormienda.', 5),

            SampleDocument::createDocument('catullo', 'odi', 'Odi et amo. Quare id faciam, fortasse requiris.', 0),
            SampleDocument::createDocument('catullo', 'odi', 'Nescio, sed fieri sentio et excrucior', 1),
        ];
        $vectorStore->addDocuments($documents);

        /** @var Document[] $retrievedDocuments */
        $retrievedDocuments = $vectorStore->fetchDocumentsByChunkRange('catullo', 'basia', 3, 5);

        expect(count($retrievedDocuments))->toBe(3);

        for ($i = 0; $i <= 2; $i++) {
            expect($retrievedDocuments[$i]->content)->toBe($documents[$i + 3]->content);
            expect($retrievedDocuments[$i]->embedding)->toBe($documents[$i + 3]->embedding);
        }
    });

    it('fails for a missing attribute', function () {
        #[Document]
        class MissingAttributeDocument extends DoctrineODMEmbeddingEntityBase
        {
        }

        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            MissingAttributeDocument::class,
        );

        $vectorStore->similaritySearch([]);
    })->throws(
        sprintf('No VectorSearchIndex attribute found on document class %s.', MissingAttributeDocument::class)
    );

    it('fails for a mismatching attribute and index', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            SingleVectorFieldDocument::class,
            'non_existent_index',
        );

        $vectorStore->similaritySearch([]);
    })->throws(
        sprintf(
            'No VectorSearchIndex attribute found on document class %s for index name %s.',
            SingleVectorFieldDocument::class,
            'non_existent_index'
        )
    );

    it('fails for missing path argument', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            MultipleVectorFieldsDocument::class,
        );

        $vectorStore->similaritySearch([]);
    })->throws(
        sprintf(
            'Multiple vector fields found on document class %s. You must specify the "path" in additionalArguments.',
            MultipleVectorFieldsDocument::class
        )
    );

    it('fails for missing vector field', function () {
        $vectorStore = new DoctrineODMVectorStore(
            getDocumentManager(),
            MultipleVectorFieldsDocument::class,
        );

        $vectorStore->similaritySearch([]);
    })->throws(
        sprintf(
            'No vector field found on document class %s for index name %s.',
            DocumentWithoutVectorField::class,
            'default',
        )
    );
});

function getDocumentManager(): DocumentManager
{
    $config = new Configuration();
    $config->setDefaultDB(MONGODB_TEST_DB);
    $config->setProxyDir(__DIR__.'/generated/proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__.'/generated/hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__.'/Document'));

    return DocumentManager::create(
        client: new Client(getenv('MONGODB_CONNECTION_STRING')),
        config: $config,
    );
}
