<?php

declare(strict_types=1);

namespace Tests\Integration\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\MultiQuery;

function chatForSemanticSearchTest(): ChatInterface
{
    $config = new OpenAIConfig();
    $config->model = 'gpt-3.5-turbo-16k';

    return new OpenAIChat($config);
}

it('Returns an array whose first line is the original query', function () {
    $multiQuery = new MultiQuery(chatForSemanticSearchTest());

    $originalQuery = 'Who wrote the music of "La traviata"?';
    $queries = $multiQuery->transformQuery($originalQuery);

    // Sample answers:
    //'Who composed the music for "La traviata"?', 'Who is the composer of the music in "La traviata"?', 'Can you tell me the name of the composer of the music in "La traviata"?'

    expect($queries)->toHaveCount(4)
        ->and($queries[0])->toBe($originalQuery);

    foreach ($queries as $query) {
        expect($query)->toContain('"La traviata"');
    }
});
