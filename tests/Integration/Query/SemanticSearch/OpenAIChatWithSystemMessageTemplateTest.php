<?php

declare(strict_types=1);

namespace Tests\Integration\Query\SemanticSearch;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

it('can generate some stuff', function () {

    $config = new OpenAIConfig();

    $chat = new OpenAIChat($config);

    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $qa = new QuestionAnswering(
        new MemoryVectorStore(),
        $embeddingGenerator,
        $chat
    );

    $qa->systemMessageTemplate = 'your name is Ciro. \\n\\n{context}.';

    $response = $qa->answerQuestion('what is your name ?');
    expect($response)->toContain('Ciro');
});
