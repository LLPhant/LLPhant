<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;
use OpenAI;

it('OpenAIChat generateChat loops for MULTIPLE rounds of tool calls', function () {
    $openAIAnswer1 = <<<'JSON'
    {
      "id": "chatcmpl-1",
      "object": "chat.completion",
      "created": 1677652288,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": null,
          "tool_calls": [{ "id": "call_1", "type": "function", "function": { "name": "tool1", "arguments": "{}" } }]
        },
        "finish_reason": "tool_calls"
      }]
    }
    JSON;

    $openAIAnswer2 = <<<'JSON'
    {
      "id": "chatcmpl-2",
      "object": "chat.completion",
      "created": 1677652289,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": null,
          "tool_calls": [{ "id": "call_2", "type": "function", "function": { "name": "tool2", "arguments": "{}" } }]
        },
        "finish_reason": "tool_calls"
      }]
    }
    JSON;

    $openAIAnswer3 = <<<'JSON'
    {
      "id": "chatcmpl-3",
      "object": "chat.completion",
      "created": 1677652290,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": "Final Answer"
        },
        "finish_reason": "stop"
      }]
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer1),
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer2),
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer3),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new GuzzleClient(['handler' => $handlerStack]);

    $openAIClient = OpenAI::factory()
        ->withApiKey('fake-key')
        ->withHttpClient($httpClient)
        ->make();

    $config = new OpenAIConfig();
    $config->model = 'gpt-3.5-turbo';
    $config->client = $openAIClient;
    $chat = new OpenAIChat($config);

    $obj = new class
    {
        public int $calls = 0;

        public function tool1()
        {
            $this->calls++;

            return 'res1';
        }

        public function tool2()
        {
            $this->calls++;

            return 'res2';
        }
    };

    $chat->addTool(new FunctionInfo('tool1', $obj, 'desc', []));
    $chat->addTool(new FunctionInfo('tool2', $obj, 'desc', []));

    $response = $chat->generateChat([Message::user('trigger tools')]);

    expect($response)->toBe('Final Answer');
    expect($obj->calls)->toBe(2);
});
