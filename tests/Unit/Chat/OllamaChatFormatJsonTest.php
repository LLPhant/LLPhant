<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\Message;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use LLPhant\Utility;

it('sends format json parameter when enabled', function () {
    $ollamaAnswer = <<<'JSON'
    {
      "message": {
        "role": "assistant",
        "content": "{\"answer\": \"json\"}"
      },
      "done": true
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, [], $ollamaAnswer),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new OllamaConfig();
    $config->model = 'test';
    $config->formatJson = true;
    $chat = new OllamaChat($config);
    $chat->client = $client;

    $chat->generateChat([Message::user('test')]);

    $lastRequest = $mock->getLastRequest();
    $payload = Utility::decodeJson($lastRequest->getBody()->getContents());

    expect($payload['format'])->toBe('json');
});
