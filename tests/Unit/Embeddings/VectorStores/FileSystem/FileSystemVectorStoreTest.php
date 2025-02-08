<?php

namespace Tests\Unit\Chat;

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use Tests\Fixtures\DocumentFixtures;
use Tests\TestCase;

describe('FileSystemVectorStore', function () {

    beforeEach(function (): void {
        /** @var TestCase $this */
        $this->fileSystemVectorStore = new FileSystemVectorStore();
        $this->fileSystemVectorStore->deleteStore();
    });

    afterEach(function (): void {
        /** @var TestCase $this */
        $this->fileSystemVectorStore->deleteStore();
    });

    it('can add and retrieve documents', function () {
        /** @var TestCase $this */
        $documents = [
            DocumentFixtures::documentWitEmbedding('First example', [0.0, 0.0]),
            DocumentFixtures::documentWitEmbedding('Second example', [10.0, 10.0]),
        ];
        $this->fileSystemVectorStore->addDocuments($documents);

        $result = $this->fileSystemVectorStore->similaritySearch([11.0, 11.0], 1);
        expect(\count($result))->toBe(1)
            ->and($result[0]->content)->toBe('Second example');

        $this->fileSystemVectorStore->addDocument(
            DocumentFixtures::documentWitEmbedding('Third example', [10.5, 10.5]),
        );

        $result = $this->fileSystemVectorStore->similaritySearch([11.0, 11.0], 2);
        expect(\count($result))->toBe(2)
            ->and($result[0]->content)->toBe('Third example')
            ->and($result[1]->content)->toBe('Second example');
    });

    it('can fetch documents by chunk range', function () {
        /** @var TestCase $this */
        $this->fileSystemVectorStore->addDocuments([
            DocumentFixtures::documentChunk(1, 'typex', 'namey'),
            DocumentFixtures::documentChunk(0, 'typex', 'namey'),
            DocumentFixtures::documentChunk(3, 'typex', 'namey'),
            DocumentFixtures::documentChunk(2, 'typex', 'namey'),
            DocumentFixtures::documentChunk(4, 'typex', 'namey'),
            DocumentFixtures::documentChunk(0, 'typex', 'namez'),
            DocumentFixtures::documentChunk(1, 'typex', 'namez'),
            DocumentFixtures::documentChunk(2, 'typex', 'namez'),
            DocumentFixtures::documentChunk(0, 'typez', 'namey'),
            DocumentFixtures::documentChunk(1, 'typez', 'namey'),
            DocumentFixtures::documentChunk(2, 'typez', 'namey'),
        ]);

        $range = $this->fileSystemVectorStore->fetchDocumentsByChunkRange('typex', 'namey', 0, 2);
        expect(\array_map(fn ($d) => DocumentUtils::getUniqueId($d), $range))->toBe(
            [
                DocumentUtils::getUniqueId(DocumentFixtures::documentChunk(0, 'typex', 'namey')),
                DocumentUtils::getUniqueId(DocumentFixtures::documentChunk(1, 'typex', 'namey')),
                DocumentUtils::getUniqueId(DocumentFixtures::documentChunk(2, 'typex', 'namey')),
            ]
        );
    });

    it('can convert data from previous format', function () {
        $this->fileSystemVectorStore->convertFromOldFileFormat(__DIR__.'/old_format_vector_store.json');
        expect($this->fileSystemVectorStore->getNumberOfDocuments())->toBe(11);
    });
});
