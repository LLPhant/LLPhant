<?php

namespace Tests\Unit\Embedding\EmbeddingGenerator\VoyageAI;

use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\VoyageAI\Voyage3EmbeddingGenerator;
use LLPhant\VoyageAIConfig;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

function voyageGeneratorAnswering(int $status, string $body): Voyage3EmbeddingGenerator
{
    putenv('VOYAGE_AI_API_KEY=fake-key');

    // A PSR-18 client does not throw on 4xx/5xx responses
    $client = new class($status, $body) implements ClientInterface
    {
        public function __construct(private readonly int $status, private readonly string $body)
        {
        }

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            return new Response($this->status, [], $this->body);
        }
    };

    return new Voyage3EmbeddingGenerator(new VoyageAIConfig(), $client);
}

it('throws the API error when embedding a text fails', function () {
    voyageGeneratorAnswering(429, '{"detail": "Rate limit reached"}')->embedText('Sample text');
})->throws(\Exception::class, 'Rate limit reached');

it('throws the API error when embedding documents fails', function () {
    voyageGeneratorAnswering(401, '{"detail": "Invalid API key"}')->embedDocuments(DocumentUtils::documents('Sample document'));
})->throws(\Exception::class, 'Invalid API key');

it('still embeds a text when the API answers successfully', function () {
    $generator = voyageGeneratorAnswering(200, '{"data": [{"embedding": [0.1, 0.2]}]}');

    expect($generator->embedText('Sample text'))->toBe([0.1, 0.2]);
});
