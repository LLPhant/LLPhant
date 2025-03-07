<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\MistralJsonResponseModifier;
use Psr\Http\Message\ResponseInterface;

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

    $modifier = MistralJsonResponseModifier::getModifierFunction();

    $response = new Response(headers: ['Content-Type' => 'application/json'], body: $originalResponse);

    /**
     * @var ResponseInterface $newResponse
     */
    $newResponse = $modifier($response);

    $expectedResponse = <<<'JSON'
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
    JSON;

    expect($newResponse->getBody()->getContents())->toMatchJson($expectedResponse);
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

    $modifier = MistralJsonResponseModifier::getModifierFunction();

    $response = new Response(headers: ['Content-Type' => 'text/plain'], body: $originalResponse);

    /**
     * @var ResponseInterface $newResponse
     */
    $newResponse = $modifier($response);

    expect($newResponse->getBody()->getContents())->toMatchJson($originalResponse);
});
