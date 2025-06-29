<?php

namespace LLPhant\Chat;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class MistralJsonResponseModifier implements ClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        return $this->processResponse($response);
    }

    private function processResponse(ResponseInterface $response): ResponseInterface
    {
        if (! $this->isJson($response)) {
            return $response;
        }

        try {
            $body = $response->getBody();
            $bodyString = $body->getContents();
            $body->rewind(); // Reset the stream position

            if (! str_contains($bodyString, 'tool_calls')) {
                return $response;
            }

            $data = json_decode($bodyString, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                return $response; // Not valid JSON
            }

            if (! isset($data['choices'])) {
                return $response;
            }

            $data = $this->processChoices($data);

            return $response->withBody(
                $this->createStream(json_encode($data, JSON_THROW_ON_ERROR))
            );
        } catch (\Exception) {
            return $response;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function processChoices(array &$data): array
    {
        foreach ($data['choices'] as &$choice) {
            if (isset($choice['message']['tool_calls'])) {
                $choice['message']['tool_calls'] = $this->processToolCalls($choice['message']['tool_calls']);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $calls
     * @return array<string, mixed>
     */
    private function processToolCalls(array &$calls): array
    {
        foreach ($calls as &$call) {
            if (! isset($call['type']) || $call['type'] !== 'function') {
                $call['type'] = 'function';
            }
        }

        return $calls;
    }

    private function isJson(ResponseInterface $response): bool
    {
        if (! $response->hasHeader('Content-Type')) {
            return false;
        }

        return str_contains($response->getHeaderLine('Content-Type'), 'application/json');
    }

    private function createStream(string $content): StreamInterface
    {
        $stream = $this->streamFactory->createStream($content);
        $stream->rewind();

        return $stream;
    }
}
