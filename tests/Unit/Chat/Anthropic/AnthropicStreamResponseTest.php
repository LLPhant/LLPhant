<?php

namespace Tests\Unit\Chat\Anthropic;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\Anthropic\AnthropicStreamResponse;
use LLPhant\Exception\HttpException;

function anthropicStream(string ...$events): AnthropicStreamResponse
{
    $body = '';
    foreach ($events as $event) {
        $body .= $event."\n\n";
    }

    return new AnthropicStreamResponse(new Response(200, [], $body), Utils::streamFor(''));
}

it('returns the text of the stream', function () {
    $stream = anthropicStream(
        'event: content_block_delta'."\n".'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hel"}}',
        'event: content_block_delta'."\n".'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"lo"}}',
        'event: message_stop'."\n".'data: {"type":"message_stop"}',
    )->getStream();

    expect($stream->getContents())->toBe('Hello');
});

it('throws an HttpException that names the error event of the stream', function () {
    $stream = anthropicStream(
        'event: content_block_delta'."\n".'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hel"}}',
        'event: error'."\n".'data: {"type":"error","error":{"type":"overloaded_error","message":"Overloaded"}}',
    );

    expect(fn () => $stream->getStream())
        ->toThrow(HttpException::class, 'overloaded_error: Overloaded');
});

it('counts the input tokens that the message_start event reports', function () {
    $response = anthropicStream(
        'event: message_start'."\n".'data: {"type":"message_start","message":{"id":"msg_1","type":"message","role":"assistant","content":[],"usage":{"input_tokens":25,"output_tokens":1}}}',
        'event: content_block_delta'."\n".'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hello"}}',
        'event: message_delta'."\n".'data: {"type":"message_delta","delta":{"stop_reason":"end_turn"},"usage":{"output_tokens":15}}',
        'event: message_stop'."\n".'data: {"type":"message_stop"}',
    );
    $response->getStream();

    expect($response->getTotalTokens())->toBe(40);
});

it('counts the cache tokens that the message_start event reports', function () {
    $response = anthropicStream(
        'event: message_start'."\n".'data: {"type":"message_start","message":{"id":"msg_1","type":"message","role":"assistant","content":[],"usage":{"input_tokens":5,"cache_creation_input_tokens":100,"cache_read_input_tokens":2000,"output_tokens":1}}}',
        'event: message_delta'."\n".'data: {"type":"message_delta","delta":{"stop_reason":"end_turn"},"usage":{"output_tokens":15}}',
        'event: message_stop'."\n".'data: {"type":"message_stop"}',
    );
    $response->getStream();

    expect($response->getTotalTokens())->toBe(2120);
});

it('does not count the input tokens twice when the message_delta event repeats them', function () {
    $response = anthropicStream(
        'event: message_start'."\n".'data: {"type":"message_start","message":{"id":"msg_1","type":"message","role":"assistant","content":[],"usage":{"input_tokens":25,"output_tokens":1}}}',
        'event: message_delta'."\n".'data: {"type":"message_delta","delta":{"stop_reason":"end_turn"},"usage":{"input_tokens":25,"output_tokens":15}}',
        'event: message_stop'."\n".'data: {"type":"message_stop"}',
    );
    $response->getStream();

    expect($response->getTotalTokens())->toBe(40);
});
