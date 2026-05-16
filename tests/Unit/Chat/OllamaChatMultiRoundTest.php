<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\Message;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use LLPhant\Utility;

it('OllamaChat generateChat loops for MULTIPLE rounds of tool calls and maintains history', function () {
    $ollamaAnswer1 = <<<'JSON'
    {
      "message": {
        "role": "assistant",
        "content": "",
        "tool_calls": [
          {
            "function": {
              "name": "tool1",
              "arguments": {}
            }
          }
        ]
      },
      "done": true
    }
    JSON;

    $ollamaAnswer2 = <<<'JSON'
    {
      "message": {
        "role": "assistant",
        "content": "Final Answer"
      },
      "done": true
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, [], $ollamaAnswer1),
        new Response(200, [], $ollamaAnswer2),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config);
    $chat->client = $client;

    $obj = new class
    {
        public int $calls = 0;

        public function tool1()
        {
            $this->calls++;

            return 'res1';
        }
    };

    $chat->addTool(new FunctionInfo('tool1', $obj, 'desc', []));

    $response = $chat->generateChat([Message::user('trigger tools')]);

    expect($response)->toBe('Final Answer');
    expect($obj->calls)->toBe(1);

    // Verify the second request payload contains the assistant message AND the tool result
    $lastRequest = $mock->getLastRequest();
    $payload = Utility::decodeJson($lastRequest->getBody()->getContents());
    $messages = $payload['messages'];

    // Expected messages: system (if any), user, assistant (tool call), tool (result)
    // OllamaChat adds system message automatically if set.

    $assistantMessageFound = false;
    $toolMessageFound = false;
    foreach ($messages as $msg) {
        if ($msg['role'] === 'assistant' && isset($msg['tool_calls'])) {
            $assistantMessageFound = true;
        }
        if ($msg['role'] === 'tool') {
            $toolMessageFound = true;
        }
    }

    expect($assistantMessageFound)->toBeTrue('Assistant message with tool_calls is missing from history');
    expect($toolMessageFound)->toBeTrue('Tool result message is missing from history');
});
