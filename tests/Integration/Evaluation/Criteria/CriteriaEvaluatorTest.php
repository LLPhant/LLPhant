<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Evaluation\Criteria\CriteriaEvaluator;
use LLPhant\Evaluation\Criteria\CriteriaEvaluatorPromptBuilder;
use LLPhant\Query\SemanticSearch\ChatSession;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

it('can evaluate Message using criteria evaluator', function () {
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();

    $chat = new OpenAIChat();
    $evaluator->setChat($chat);
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);
    $resultsMessage = $evaluator->evaluateMessages([Message::user('2+2=?'), Message::assistant('4')]);
    expect($resultsMessage->getResults())->toBe([
        'correctness' => 5,
        'helpfulness' => 5,
        'relevance' => 5,
    ]);
});

it('can evaluate ChatSession using criteria evaluator', function () {
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();

    $chat = new OpenAIChat();
    $chatSession = new ChatSession();
    $qa = new QuestionAnswering(
        new MemoryVectorStore(),
        new OpenAI3SmallEmbeddingGenerator(),
        $chat,
        session: $chatSession
    );

    $evaluator->setChat($chat);
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);

    $question = 'What is the name of the first official Roman Emperor?';
    $qa->answerQuestion($question);
    $resultsSession = $evaluator->evaluateChatSession($chatSession);
    expect($resultsSession->getResults())->toBe([
        'correctness' => 5,
        'helpfulness' => 5,
        'relevance' => 5,
    ]);
});
