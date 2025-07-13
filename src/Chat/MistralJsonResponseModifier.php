<?php

namespace LLPhant\Chat;

use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

class MistralJsonResponseModifier
{
    public static function createResponseModifier(): callable
    {
        return Middleware::mapResponse(self::getModifierFunction());
    }

    /**
     * Public just for testing purposes
     */
    public static function getModifierFunction(): \Closure
    {
        return function (ResponseInterface $response): ResponseInterface {
            if (self::isJson($response)) {
                $modifiedResponse = self::processJsonResponse($response);
                if ($modifiedResponse instanceof ResponseInterface) {
                    return $modifiedResponse;
                }
            }

            return $response;
        };
    }

    private static function processJsonResponse(ResponseInterface $response): ?ResponseInterface
    {
        try {
            $body = $response->getBody();
            $bodyString = $body->getContents();
            $body->seek(0); // Reset the stream position

            if (! str_contains($bodyString, 'tool_calls')) {
                return null;
            }

            $data = json_decode($bodyString, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                return null; // Not valid JSON
            }

            if (! isset($data['choices'])) {
                return null;
            }

            $data = self::processChoices($data);

            $stream = fopen('php://temp', 'r+');
            if ($stream === false) {
                return null;
            }
            fwrite($stream, json_encode($data, JSON_THROW_ON_ERROR));
            rewind($stream);

            return $response->withBody(
                new Stream($stream),
            );

        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function processChoices(array &$data): array
    {
        foreach ($data['choices'] as &$choice) {
            if (isset($choice['message']['tool_calls'])) {
                $choice['message']['tool_calls'] = self::processToolCalls($choice['message']['tool_calls']);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $calls
     * @return array<string, mixed>
     */
    private static function processToolCalls(array &$calls): array
    {
        foreach ($calls as &$call) {
            if (! isset($call['type']) || $call['type'] !== 'function') {
                $call['type'] = 'function';
            }
        }

        return $calls;
    }

    private static function isJson(ResponseInterface $response): bool
    {
        if (! $response->hasHeader('Content-Type')) {
            return false;
        }

        return str_contains($response->getHeaderLine('Content-Type'), 'application/json');
    }
}
