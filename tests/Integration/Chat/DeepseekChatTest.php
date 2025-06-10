<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\DeepseekChat;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\DeepseekConfig;

function deepseekChat(): DeepseekChat
{
    $config = new DeepseekConfig();
    $config->apiKey = getenv('DEEPSEEK_API_KEY');

    return new DeepseekChat($config);
}

it('can generate some stuff', function () {
    $chat = deepseekChat();
    $response = $chat->generateText('what is one + one?');
    expect($response)->toBeString();
});

it('can generate some stuff with a system prompt', function () {
    $chat = deepseekChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one?');
    expect(strtolower($response))->toStartWith('ok');
});

it('can generate chat with a system prompt', function () {
    $chat = deepseekChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $messages = [
        Message::user('what is one + one?'),
    ];
    $response = $chat->generateChat($messages);
    expect(strtolower($response))->toStartWith('ok');
});

it('can generate some stuff using a stream', function () {
    $chat = deepseekChat();
    $stream = $chat->generateStreamOfText('What is 2+2?');
    $content = '';
    while (! $stream->eof()) {
        $content .= $stream->read(1024);
    }
    expect($content)->toBeString()->and($content)->not->toBeEmpty();
});

it('can generate chat using a stream', function () {
    $chat = deepseekChat();
    $stream = $chat->generateChatStream([
        Message::user('What is 2+2?'),
    ]);
    $content = '';
    while (! $stream->eof()) {
        $content .= $stream->read(1024);
    }
    expect($content)->toBeString()->and($content)->not->toBeEmpty();
});

it('can call a function and provide the result to the assistant', function () {
    $chat = deepseekChat();
    $weatherInstance = new class
    {
        public function get_weather(string $location): string
        {
            return "Weather in $location: Sunny";
        }
    };

    $parameters = [
        new Parameter('location', 'string', 'The location to get weather for'),
    ];

    $function = new FunctionInfo(
        'get_weather',
        $weatherInstance,
        'Get weather for a location',
        $parameters,
        $parameters
    );

    $chat->addTool($function);
    $chat->setSystemMessage('You are an AI that answers to questions about weather in certain locations by calling external services to get the information');

    $messages = [
        Message::user('What is the weather in Venice?'),
    ];
    $toolsCalled = $chat->generateChatOrReturnFunctionCalled($messages);

    $firstTool = $toolsCalled[0];
    expect($firstTool->name)->toBe('get_weather');
});
