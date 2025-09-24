<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Chat\Message;
use LLPhant\Chat\MistralAIChat;
use LLPhant\Exception\HttpException;
use LLPhant\MistralAIConfig;

it('can generate some stuff', function () {
    $chat = new MistralAIChat();
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff with a system prompt', function () {
    $chat = new MistralAIChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one ?');
    expect(strtolower($response))->toBe('ok');
});

it('can load any existing model', function () {
    $config = new MistralAIConfig();
    $config->model = 'mistral-tiny';
    $chat = new MistralAIChat($config);
    $response = $chat->generateText('one + one ?');
    expect($response)->toBeString();
});

it('calls tool functions during a chat', function () {
    $config = new MistralAIConfig();
    $chat = new MistralAIChat($config);

    $notifier = new NotificationExample();

    $functionSendNotification = FunctionBuilder::buildFunctionInfo($notifier, 'sendNotificationToSlack');

    $chat->addTool($functionSendNotification);
    $messages = [
        Message::system('You need to call the function to send a confirmation notification to slack'),
        Message::user('the confirmation should be called'),
    ];

    $answer = $chat->generateChat($messages);

    expect($notifier->nrOfCalls)->toBe(1);
    expect($answer)->toBeString();
});

it('reports errors correctly', function () {
    $config = new MistralAIConfig();
    $config->apiKey = 'fake key';
    $chat = new MistralAIChat($config);
    $chat->generateText('Hello!');
})->throws(HttpException::class, '401');
