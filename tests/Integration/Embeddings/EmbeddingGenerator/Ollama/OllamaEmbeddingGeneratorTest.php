<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\EmbeddingGenerator;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\OllamaConfig;

it('can embed some stuff', function () {
    $config = new OllamaConfig();
    $config->model = 'nomic-embed-text';
    $config->modelOptions = ['num_ctx' => 1000000];
    $config->url = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/';

    $embeddingGenerator = new OllamaEmbeddingGenerator($config);
    $embedding = $embeddingGenerator->embedText('I love food');
    expect($embedding[0])->toBeFloat();
});

it('can embed batch stuff', function () {
    $config = new OllamaConfig();
    $config->model = 'nomic-embed-text';
    $config->url = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/';

    $embeddingGenerator = new OllamaEmbeddingGenerator($config);

    $doc1 = new Document();
    $doc1->content = 'I love Italian food';

    $doc2 = new Document();
    $doc2->content = 'I love French food';

    $docs = $embeddingGenerator->embedDocuments([$doc1, $doc2]);
    expect($docs[0]->embedding[0])->toBeFloat();
});
