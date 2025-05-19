<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\MistralJsonResponseModifier;
use Mockery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

it('can change JSON without tool_calls type', function () {
    $originalResponse = <<<'JSON'
    {
      "id": "cmpl-e5cc70bb28c444948073e77776eb30ef",
      "object": "chat.completion",
      "model": "mistral-small-latest",
      "usage": {
        "prompt_tokens": 16,
        "completion_tokens": 34,
        "total_tokens": 50
      },
      "created": 1702256327,
      "choices": [
        {
          "index": 0,
          "message": {
            "content": "string",
            "tool_calls": [
              {
                "id": "null",
                "function": {
                  "name": "string",
                  "arguments": {}
                },
                "index": 0
              }
            ],
            "prefix": false,
            "role": "assistant"
          },
          "finish_reason": "stop"
        }
      ]
    }
    JSON;

    // Mock the HTTP client
    $httpClient = Mockery::mock(ClientInterface::class);
    $httpClient->shouldReceive('sendRequest')
        ->once()
        ->andReturn(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $originalResponse
        ));

    // Mock the stream factory
    $modifiedStream = Mockery::mock(StreamInterface::class);
    $modifiedStream->shouldReceive('rewind')->once()->andReturnSelf();
    $modifiedStream->shouldReceive('getContents')->andReturn(json_encode(json_decode(
        <<<'JSON'
        {
          "id": "cmpl-e5cc70bb28c444948073e77776eb30ef",
          "object": "chat.completion",
          "model": "mistral-small-latest",
          "usage": {
            "prompt_tokens": 16,
            "completion_tokens": 34,
            "total_tokens": 50
          },
          "created": 1702256327,
          "choices": [
            {
              "index": 0,
              "message": {
                "content": "string",
                "tool_calls": [
                  {
                    "id": "null",
                    "type": "function",
                    "function": {
                      "name": "string",
                      "arguments": {}
                    },
                    "index": 0
                  }
                ],
                "prefix": false,
                "role": "assistant"
              },
              "finish_reason": "stop"
            }
          ]
        }
        JSON
    )));

    $streamFactory = Mockery::mock(StreamFactoryInterface::class);
    $streamFactory->shouldReceive('createStream')
        ->once()
        ->andReturn($modifiedStream);

    // Create the modifier and test it
    $modifier = new MistralJsonResponseModifier($httpClient, $streamFactory);
    $request = Mockery::mock(RequestInterface::class);
    $response = $modifier->sendRequest($request);

    expect($response)->toBeInstanceOf(ResponseInterface::class);
    expect($response->getBody()->getContents())->toContain('"type":"function"');
});

it('does not change payloads with wrong header', function () {
    $originalResponse = <<<'JSON'
    {
      "id": "cmpl-e5cc70bb28c444948073e77776eb30ef",
      "object": "chat.completion",
      "model": "mistral-small-latest",
      "usage": {
        "prompt_tokens": 16,
        "completion_tokens": 34,
        "total_tokens": 50
      },
      "created": 1702256327,
      "choices": [
        {
          "index": 0,
          "message": {
            "content": "string",
            "tool_calls": [
              {
                "id": "null",
                "function": {
                  "name": "string",
                  "arguments": {}
                },
                "index": 0
              }
            ],
            "prefix": false,
            "role": "assistant"
          },
          "finish_reason": "stop"
        }
      ]
    }
    JSON;

    // Mock the HTTP client
    $httpClient = Mockery::mock(ClientInterface::class);
    $httpClient->shouldReceive('sendRequest')
        ->once()
        ->andReturn(new Response(
            200,
            ['Content-Type' => 'text/plain'],
            $originalResponse
        ));

    // Mock the stream factory (shouldn't be used in this case)
    $streamFactory = Mockery::mock(StreamFactoryInterface::class);
    $streamFactory->shouldReceive('createStream')->never();

    // Create the modifier and test it
    $modifier = new MistralJsonResponseModifier($httpClient, $streamFactory);
    $request = Mockery::mock(RequestInterface::class);
    $response = $modifier->sendRequest($request);

    expect($response)->toBeInstanceOf(ResponseInterface::class);
    expect($response->getHeaderLine('Content-Type'))->toBe('text/plain');
});
