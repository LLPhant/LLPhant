<?php

namespace Tests\Chat;

use LLPhant\Chat\Message;
use LLPhant\Query\SemanticSearch\ChatSession;

test('it remembers chat history as an array', function () {
    $session = new ChatSession();

    expect($session->getHistory())->toBeEmpty();

    $firstQuestion = Message::user('What is the name of the first Roman Emperor?');
    $session->addMessage($firstQuestion);

    $firstAnswer = Message::assistant('Augustus was the first Roman Emperor');
    $session->addMessage($firstAnswer);

    $secondQuestion = Message::user('And who was the third one?');
    $session->addMessage($secondQuestion);

    $secondAnswer = Message::assistant('Caligula was the third Roman Emperor');
    $session->addMessage($secondAnswer);

    expect($session->getHistory())->toBe([
        $firstQuestion,
        $firstAnswer,
        $secondQuestion,
        $secondAnswer,
    ]);
});

test('it remembers chat history as string', function () {
    $session = new ChatSession();

    expect($session->getHistoryAsString())->toBeEmpty();

    $firstQuestion = Message::user('What is the name of the first Roman Emperor?');
    $session->addMessage($firstQuestion);

    $firstAnswer = Message::assistant('Augustus was the first Roman Emperor');
    $session->addMessage($firstAnswer);

    $secondQuestion = Message::user('And who was the third one?');
    $session->addMessage($secondQuestion);

    $secondAnswer = Message::assistant('Caligula was the third Roman Emperor');
    $session->addMessage($secondAnswer);

    expect($session->getHistoryAsString())
        ->toBe(
            <<<'END'
user: What is the name of the first Roman Emperor?
assistant: Augustus was the first Roman Emperor
user: And who was the third one?
assistant: Caligula was the third Roman Emperor
END);
});
