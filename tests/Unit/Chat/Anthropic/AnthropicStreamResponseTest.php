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
