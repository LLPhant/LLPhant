<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\AnthropicConfig;
use LLPhant\Chat\AnthropicChat;
use LLPhant\Chat\Message;
use Psr\Http\Message\StreamInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

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
