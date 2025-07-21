<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\Message;
use LLPhant\Embeddings\Distances\EuclideanDistanceL2;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAIADA002EmbeddingGenerator;
use LLPhant\Evaluation\EmbeddingDistance\EmbeddingDistanceEvaluator;

it('can evaluate text using embedding distance evaluator', function (): void {

    $evaluator = new EmbeddingDistanceEvaluator(new OpenAIADA002EmbeddingGenerator, new EuclideanDistanceL2);

    $results = $evaluator->evaluateText('pink elephant walks with the suitcase', 'pink cheesecake is jumping over the suitcase with dinosaurs');
    expect($results->getResults())->toBe(['distance' => 0.474]);
});

it('can evaluate Messages using embedding distance evaluator', function (): void {
    $evaluator = new EmbeddingDistanceEvaluator(new OpenAIADA002EmbeddingGenerator, new EuclideanDistanceL2);

    $candidateMessages = [
        Message::assistant('pink elephant walks with the suitcase'),
        Message::assistant('bla bla bla'),
    ];
    $referenceMessages = [
        'pink cheesecake is jumping over the suitcase with dinosaurs',
        'lorem ipsum',
    ];
    $results = $evaluator->evaluateMessages($candidateMessages, $referenceMessages)->getResults();
    expect(\count($results))->toBe(2);
    expect($results[0])->toBeNearTo(0.474, 0.01);
    expect($results[1])->toBeNearTo(0.572, 0.01);
});
