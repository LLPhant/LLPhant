<?php

use LLPhant\Chat\ChatInterface;
use LLPhant\Query\SemanticSearch\MultiQuery;

it('Returns an array whose first line is the original query', function () {
    $answer = "First line\nSecond line\nThird line";
    $mockChat = Mockery::mock(ChatInterface::class);
    $mockChat->allows([
        'setSystemMessage' => null,
        'generateText' => $answer,
    ]);

    $multiQuery = new MultiQuery($mockChat);

    expect($multiQuery->transformQuery('Original query'))
        ->toBe(['Original query', 'First line', 'Second line', 'Third line']);
});
