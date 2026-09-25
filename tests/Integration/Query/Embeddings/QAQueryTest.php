<?php

declare(strict_types=1);

namespace Tests\Integration\Query\Embeddings;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\Distances\CosineDistance;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAIADA002EmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use LLPhant\Query\SemanticSearch\MultiQuery;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

beforeEach(function () {
    $tempFilePaths = [\sys_get_temp_dir().'/QAQueryTest.json', \sys_get_temp_dir().'/QAQueryTest_cosine.json'];
    foreach ($tempFilePaths as $filePath) {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
});

it('generates an answer based on private knowledge', function (VectorStoreBase $vectorStore) {
    $dataReader = new FileDataReader(__DIR__.'/private-data.txt');
    $documents = $dataReader->getDocuments();

    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 500);

    $embeddingGenerator = new OpenAIADA002EmbeddingGenerator();
    $embeddedDocuments = $embeddingGenerator->embedDocuments($splittedDocuments);

    $vectorStore->addDocuments($embeddedDocuments);

    $qa = new QuestionAnswering(
        $vectorStore,
        $embeddingGenerator,
        new OpenAIChat()
    );

    $answer = $qa->answerQuestion('what is the secret of Alice?');

    expect($answer)->toContain('cheese')
        ->and($qa->getTotalTokens() > 0)->toBeTrue();
})->with([
    new MemoryVectorStore(),
    new FileSystemVectorStore(\sys_get_temp_dir().'/QAQueryTest.json'),
    new MemoryVectorStore(new CosineDistance()),
    new FileSystemVectorStore(\sys_get_temp_dir().'/QAQueryTest_cosine.json', new CosineDistance()),
]);

it('generates an answer using MultiQuery', function () {
    $vectorStore = new MemoryVectorStore();

    $dataReader = new FileDataReader(__DIR__.'/private-data.txt');
    $documents = $dataReader->getDocuments();

    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 500);

    $embeddingGenerator = new OpenAIADA002EmbeddingGenerator();
    $embeddedDocuments = $embeddingGenerator->embedDocuments($splittedDocuments);

    $vectorStore->addDocuments($embeddedDocuments);

    $chat = new OpenAIChat();

    $qa = new QuestionAnswering(
        $vectorStore,
        $embeddingGenerator,
        $chat,
        new MultiQuery($chat)
    );

    $answer = $qa->answerQuestion('what is the secret of Alice?');

    expect($answer)->toContain('cheese');
});
