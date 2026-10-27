<?php

declare(strict_types=1);

namespace Tests\Integration\Embeddings\EmbeddingGenerator;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator;
use LLPhant\LmStudioConfig;

it('can embed some stuff', function () {
    $config = new LmStudioConfig();
    $config->model = 'text-embedding-nomic-embed-text-v2-moe';
    $config->url = getenv('LMSTUDIO_URL');

    $embeddingGenerator = new LmStudioEmbeddingGenerator($config);
    $embedding = $embeddingGenerator->embedText('I love food');
    expect($embedding[0])->toBeFloat();
});

it('can embed batch stuff', function () {
    $config = new LmStudioConfig();
    $config->model = 'text-embedding-nomic-embed-text-v2-moe';
    $config->url = getenv('LMSTUDIO_URL');

    $embeddingGenerator = new LmStudioEmbeddingGenerator($config);

    $doc1 = new Document();
    $doc1->content = 'I love Italian food';

    $doc2 = new Document();
    $doc2->content = 'I love French food';

    $docs = $embeddingGenerator->embedDocuments([$doc1, $doc2]);
    expect($docs[0]->embedding[0])->toBeFloat();
});
