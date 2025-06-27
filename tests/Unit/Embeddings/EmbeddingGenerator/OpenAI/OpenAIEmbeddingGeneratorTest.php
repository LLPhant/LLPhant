<?php

namespace Tests\Unit\Embedding\EmbeddingGenerator\OpenAI;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use Psr\Http\Client\ClientInterface;
use Tests\Unit\Chat\MockOpenAIClient;

const FAKE_EMBEDDING_ANSWER = <<<'JSON'
{
  "object": "list",
  "data": [
    {
      "object": "embedding",
      "index": 0,
      "embedding": [
        -0.006929283495992422,
        -0.005336422007530928,
        -4.547132266452536e-05,
        -0.024047505110502243
      ]
    }
  ],
  "model": "text-embedding-3-small",
  "usage": {
    "prompt_tokens": 5,
    "total_tokens": 5
  }
}
JSON;

function getEmbeddingGenerator(string $body): AbstractOpenAIEmbeddingGenerator
{
    $config = new OpenAIConfig();
    $config->apiKey = 'fake-key';
    $config->client = new MockOpenAIClient();

    $generator = new class($config) extends AbstractOpenAIEmbeddingGenerator
    {
        public string $body = '{}';

        public function getEmbeddingLength(): int
        {
            return 1;
        }

        public function getModelName(): string
        {
            return 'test';
        }

        protected function createClientForBatch(): ClientInterface
        {
            $mock = new MockHandler([
                new Response(200, [], $this->body),
            ]);
            $handlerStack = HandlerStack::create($mock);

            return new Client(['handler' => $handlerStack]);
        }
    };

    $generator->body = $body;

    return $generator;
}

it('can handle empty data gracefully', function () {
    $generator = getEmbeddingGenerator('{"object": "list", "model": "test", "usage": {"prompt_tokens": 0, "total_tokens": 0}}');

    expect($generator->embedDocuments(DocumentUtils::documents('Sample document')))->toBeArray();
});

it('can handle data in non UTF8 encodings', function () {
    $generator = getEmbeddingGenerator(FAKE_EMBEDDING_ANSWER);

    $japanese = \mb_convert_encoding('おはよう', 'EUC-JP', 'UTF-8');
    $greek = \mb_convert_encoding('Καλημέρα', 'ISO-8859-7', 'UTF-8');
    $ukrainian = \mb_convert_encoding('доброго ранку', 'ISO-8859-5', 'UTF-8');

    expect($generator->embedDocuments(DocumentUtils::documents($japanese, $greek, $ukrainian)))->toBeArray();
});

it('can embed a single document in non UTF8 encodings', function () {
    $generator = getEmbeddingGenerator(FAKE_EMBEDDING_ANSWER);

    $japanese = \mb_convert_encoding('おはよう', 'EUC-JP', 'UTF-8');

    expect($generator->embedDocument(DocumentUtils::documents($japanese)[0])->content)->tobe($japanese);
});

it('can embed text in non UTF8 encodings', function () {
    $generator = getEmbeddingGenerator(FAKE_EMBEDDING_ANSWER);

    $japanese = \mb_convert_encoding('おはよう', 'EUC-JP', 'UTF-8');

    expect($generator->embedText($japanese))->toBeArray();
});
