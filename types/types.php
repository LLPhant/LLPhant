<?php

declare(strict_types=1);

use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\ChatSession;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Psr\Http\Message\StreamInterface;

use function PHPStan\Testing\assertType;

$qa = new QuestionAnswering(
    new MemoryVectorStore(),
    new OpenAI3SmallEmbeddingGenerator(),
    new OpenAIChat(new OpenAIConfig()),
    session: new ChatSession(),
);

assertType('string', $qa->answerQuestionFromChat([], stream: false));
assertType(StreamInterface::class, $qa->answerQuestionFromChat([]));
