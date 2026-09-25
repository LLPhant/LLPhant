<?php

declare(strict_types=1);

namespace Tests\Integration\Query\SemanticSearch;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use LLPhant\Query\SemanticSearch\SiblingsDocumentTransformer;

it('can be used to get bigger chunks from small ones', function () {
    $filePath = __DIR__.'/SampleDocuments';
    $reader = new FileDataReader($filePath);
    $documents = $reader->getDocuments();
    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 5, "\n");

    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $embeddedDocuments = $embeddingGenerator->embedDocuments($splittedDocuments);

    $vectorStore = new MemoryVectorStore();
    $vectorStore->addDocuments($embeddedDocuments);

    $siblingsTransformer = new SiblingsDocumentTransformer($vectorStore, 3);
    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $qa = new QuestionAnswering(
        $vectorStore,
        $embeddingGenerator,
        new OpenAIChat(),
        retrievedDocumentsTransformer: $siblingsTransformer
    );
    $answer = $qa->answerQuestion('Can I win at cukoo if I have a coral card?');
    expect($answer)->toContain('cuckoo');
});
