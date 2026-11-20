<?php

namespace Tests\Unit\Embeddings\VectorStores\Meilisearch;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\VectorStores\Meilisearch\MeilisearchClient;
use Psr\Http\Message\RequestInterface;

class FakeMeilisearchClient
{
    /**
     * @param  Response[]  $responses
     */
    public function __construct(public readonly MeilisearchClient $client, public readonly MockHandler $handler)
    {
    }

    /** @var array<int, array{method: string, path: string, body: string}> */
    private array $requests = [];

    /**
     * @param  Response[]  $responses
     */
    public static function withResponses(array $responses): self
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);

        $fake = new self(new MeilisearchClient(host: 'http://meilisearch.test', client: $client), $mock);

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($fake): RequestInterface {
            $fake->record($request);

            return $request;
        }));

        return $fake;
    }

    public function record(RequestInterface $request): void
    {
        $body = $request->getBody();
        $recorded = (string) $body;
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $this->requests[] = [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'body' => $recorded,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requestedPaths(): array
    {
        return array_map(static fn (array $request): string => $request['path'], $this->requests);
    }

    /**
     * The last body sent to a path, which is not the last request when a write polls its task.
     */
    public function requestBody(string $path): string
    {
        foreach (array_reverse($this->requests) as $request) {
            if ($request['path'] === $path) {
                return $request['body'];
            }
        }

        return '';
    }

    public function lastRequestBody(): string
    {
        $request = $this->handler->getLastRequest();

        return $request instanceof \Psr\Http\Message\RequestInterface ? $request->getBody()->getContents() : '';
    }
}
