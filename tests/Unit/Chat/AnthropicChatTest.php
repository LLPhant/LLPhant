<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LLPhant\AnthropicConfig;
use LLPhant\Chat\AnthropicChat;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use Psr\Http\Message\StreamInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Tests\Integration\Chat\WeatherExample;

const ANTHROPIC_FAKE_JSON_ANSWER = <<<'JSON'
{
  "content": [
    {
      "text": "Hi! My name is Claude.",
      "type": "text"
    }
  ],
  "id": "msg_013Zva2CMHLNnXjNJJKqJ2EF",
  "model": "claude-3-5-sonnet-20240620",
  "role": "assistant",
  "stop_reason": "end_turn",
  "stop_sequence": null,
  "type": "message",
  "usage": {
    "input_tokens": 10,
    "output_tokens": 25
  }
}
JSON;

const ANTROPIC_FAKE_STREAM_ANSWER = <<<'TXT'
event: message_start
data: {"type": "message_start", "message": {"id": "msg_1nZdL29xx5MUA1yADyHTEsnR8uuvGzszyY", "type": "message", "role": "assistant", "content": [], "model": "claude-3-5-sonnet-20240620", "stop_reason": null, "stop_sequence": null, "usage": {"input_tokens": 25, "output_tokens": 1}}}

event: content_block_start
data: {"type": "content_block_start", "index": 0, "content_block": {"type": "text", "text": ""}}

event: ping
data: {"type": "ping"}

event: content_block_delta
data: {"type": "content_block_delta", "index": 0, "delta": {"type": "text_delta", "text": "Hello"}}

event: content_block_delta
data: {"type": "content_block_delta", "index": 0, "delta": {"type": "text_delta", "text": "!"}}

event: content_block_stop
data: {"type": "content_block_stop", "index": 0}

event: message_delta
data: {"type": "message_delta", "delta": {"stop_reason": "end_turn", "stop_sequence":null}, "usage": {"output_tokens": 15}}

event: message_stop
data: {"type": "message_stop"}
TXT;

function anthropicChatWithFakeHttpConnection(string $body, ?LoggerInterface $logger = null): AnthropicChat
{
    $mock = new MockHandler([
        new Response(200, [], $body),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new AnthropicConfig(client: $client);

    return new AnthropicChat($config, $logger);
}

it('generates a text', function () {
    $logger = new class extends AbstractLogger
    {
        public array $logs = [];

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }
    };

    $anthropicChat = anthropicChatWithFakeHttpConnection(ANTHROPIC_FAKE_JSON_ANSWER, $logger);
    $response = $anthropicChat->generateText('this is the prompt question');
    expect($response)->toBe('Hi! My name is Claude.');
    expect($anthropicChat->getTotalTokens())->toBe(35);
    expect($logger->logs)->toHaveCount(1);
    expect(array_map(fn ($l) => $l['message'], $logger->logs))->toBe(['Calling POST v1/messages']);
    expect(array_map(fn ($l) => $l['level'], $logger->logs))->toBe(['debug']);
});

it('generates a chat', function () {
    $response = anthropicChatWithFakeHttpConnection(ANTHROPIC_FAKE_JSON_ANSWER)->generateChat([Message::user('this is the prompt question')]);
    expect($response)->toBe('Hi! My name is Claude.');
});

it('returns a stream response using generateStreamOfText()', function () {
    $anthropicChat = anthropicChatWithFakeHttpConnection(ANTROPIC_FAKE_STREAM_ANSWER);
    $response = $anthropicChat->generateStreamOfText('this is the prompt question');
    expect($response)->toBeInstanceof(StreamInterface::class)->and($response->__toString())->toBe('Hello!');
    expect($anthropicChat->getTotalTokens())->toBe(15);
});

it('returns a stream response using generateChatStream()', function () {
    $response = anthropicChatWithFakeHttpConnection(ANTROPIC_FAKE_STREAM_ANSWER)->generateChatStream([Message::user('here the question')]);
    expect($response)->toBeInstanceof(StreamInterface::class)->and($response->__toString())->toBe('Hello!');
});

it('stops at tool call in generateChatOrReturnFunctionToCall', function () {
    $anthropicAnswerWithTool = <<<'JSON'
    {
      "content": [
        {
          "id": "tool_123",
          "input": {
            "location": "Venice"
          },
          "name": "currentWeatherForLocation",
          "type": "tool_use"
        }
      ],
      "id": "msg_123",
      "model": "claude-3",
      "role": "assistant",
      "stop_reason": "tool_use",
      "type": "message",
      "usage": {
        "input_tokens": 10,
        "output_tokens": 25
      }
    }
    JSON;

    $anthropicChat = anthropicChatWithFakeHttpConnection($anthropicAnswerWithTool);

    $weatherExample = new WeatherExample();
    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'description',
        [new Parameter('location', 'string', 'desc')]
    );
    $anthropicChat->addFunction($function);

    $result = $anthropicChat->generateChatOrReturnFunctionToCall([Message::user('weather in Venice?')]);

    expect($result)->toBeArray();
    expect($result[0])->toBeInstanceOf(FunctionInfo::class);
    expect($result[0]->name)->toBe('currentWeatherForLocation');
    // It should NOT have executed the function
    expect($weatherExample->lastMessage)->toBe('');
});

it('stops at tool call in generateTextOrReturnFunctionToCall', function () {
    $anthropicAnswerWithTool = <<<'JSON'
    {
      "content": [
        {
          "id": "tool_123",
          "input": {
            "location": "Venice"
          },
          "name": "currentWeatherForLocation",
          "type": "tool_use"
        }
      ],
      "id": "msg_123",
      "model": "claude-3",
      "role": "assistant",
      "stop_reason": "tool_use",
      "type": "message",
      "usage": {
        "input_tokens": 10,
        "output_tokens": 25
      }
    }
    JSON;

    $anthropicChat = anthropicChatWithFakeHttpConnection($anthropicAnswerWithTool);

    $weatherExample = new WeatherExample();
    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'description',
        [new Parameter('location', 'string', 'desc')]
    );
    $anthropicChat->addFunction($function);

    $result = $anthropicChat->generateTextOrReturnFunctionToCall('weather in Venice?');

    expect($result)->toBeArray();
    expect($result[0])->toBeInstanceOf(FunctionInfo::class);
    expect($result[0]->name)->toBe('currentWeatherForLocation');
    // It should NOT have executed the function
    expect($weatherExample->lastMessage)->toBe('');
});

it('serializes empty tool input as object in recursive anthropic calls', function () {
    $firstAnswer = <<<'JSON'
    {
      "content": [
        {
          "type": "text",
          "text": "Let me check that for you."
        },
        {
          "id": "tool_123",
          "input": {},
          "name": "getItemList",
          "type": "tool_use"
        }
      ],
      "id": "msg_123",
      "model": "claude-3",
      "role": "assistant",
      "stop_reason": "tool_use",
      "type": "message",
      "usage": {
        "input_tokens": 10,
        "output_tokens": 25
      }
    }
    JSON;

    $secondAnswer = <<<'JSON'
    {
      "content": [
        {
          "type": "text",
          "text": "The oldest wine is Barolo riserva 2015."
        }
      ],
      "id": "msg_124",
      "model": "claude-3",
      "role": "assistant",
      "stop_reason": "end_turn",
      "type": "message",
      "usage": {
        "input_tokens": 20,
        "output_tokens": 12
      }
    }
    JSON;

    $historyContainer = [];
    $history = Middleware::history($historyContainer);
    $mock = new MockHandler([
        new Response(200, [], $firstAnswer),
        new Response(200, [], $secondAnswer),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push($history);
    $client = new Client(['handler' => $handlerStack]);

    $config = new AnthropicConfig(client: $client);
    $chat = new AnthropicChat($config);

    $itemListObject = new class
    {
        public function getItemList(): array
        {
            return ['Barolo riserva 2015', 'Brunello di Montalcino 2020'];
        }
    };
    $chat->addFunction(new FunctionInfo('getItemList', $itemListObject, 'Get a list of items from my warehouse', []));

    $chat->generateText('What is the oldest wine I have in my warehouse?');

    expect($historyContainer)->toHaveCount(2);

    $secondRequestBody = (string) $historyContainer[1]['request']->getBody();
    $secondRequestPayload = json_decode($secondRequestBody, true, 512, JSON_THROW_ON_ERROR);
    expect($secondRequestPayload['messages'][1]['content'][1]['input'])->toBeArray()->toBeEmpty();
    expect($secondRequestBody)->toContain('"input":{}');
});
