<?php

declare(strict_types=1);

namespace Tests\Integration\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Enums\OpenAIChatModel;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\LLMReranker;

function chatForTest(): ChatInterface
{
    $config = new OpenAIConfig();
    $config->model = OpenAIChatModel::Gpt4Omini->value;

    return new OpenAIChat($config);
}

it('Returns an array whose first line is the best document', function () {
    $nrOfOutputDocuments = 3;
    $reranker = new LLMReranker(chatForTest(), $nrOfOutputDocuments);

    $queries = [
        'Who wrote the music of "La traviata"?',
        'Who is the composer of "La traviata"?',
    ];

    $documents = DocumentUtils::documents(
        'Teatro alla Scala is located in Milan',
        '"La Traviata" is an opera in three acts',
        'Giuseppe Verdi wrote "La Traviata" in  1853',
        'Giuseppe Verdi was born in 1813,'
    );

    $reranked = $reranker->transformDocuments($queries, $documents);
    expect(count($reranked))->toBe($nrOfOutputDocuments)
        ->and($reranked[0]->content)->toBe('Giuseppe Verdi wrote "La Traviata" in  1853');
});
