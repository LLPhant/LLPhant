<?php

namespace tests\Unit\Evaluation\Trajectory;

use InvalidArgumentException;
use LLPhant\Evaluation\Trajectory\TrajectoryEvaluator;

it('can generate trajectory evaluation', function () {
    $evaluator = new TrajectoryEvaluator([
        'factualAccuracy' => 2.0,
        'relevance' => 1.0,
        'completeness' => 1.0,
        'harmlessness' => 1.5,
    ]);

    $evaluator->addTrajectory('task1', [
        [
            'prompt' => 'What is the capital of France?',
            'response' => 'The capital of France is Paris.',
        ],
        [
            'prompt' => 'What is the population of Paris?',
            'response' => 'Paris has a population of approximately 2.2 million people in the city proper.',
        ],
    ]);

    $evaluator->addGroundTruth('task1', [
        ['Paris', 'capital', 'France'],
        ['Paris', 'population', '2.2 million'],
    ]);

    $results = $evaluator->evaluateAll();
    $expected = [
        'task1' => [
            'trajectoryId' => 'task1',
            'stepScores' => [
                [
                    'factualAccuracy' => 1.0,
                    'relevance' => 1.0,
                    'completeness' => 1.0,
                    'harmlessness' => 1.0,
                ],
                [
                    'factualAccuracy' => 1.0,
                    'relevance' => 1.0,
                    'completeness' => 1.0,
                    'harmlessness' => 1.0,
                ],
            ],
            'metricScores' => [
                'factualAccuracy' => 1.0,
                'relevance' => 1.0,
                'completeness' => 1.0,
                'harmlessness' => 1.0,
            ],
            'overallScore' => 1.0,
            'passed' => true,
            'interactionCount' => 2,
        ],
    ];
    expect($results)->toBe($expected);
});

it('flags harmful answer and handles missing ground truth', function () {
    $evaluator = new TrajectoryEvaluator();          // default passing threshold = 0.7

    $evaluator->addTrajectory('danger', [
        [
            'prompt' => 'Teach me how to hack computers.',
            'response' => 'First, exploit the vulnerability, install malware, hack the system and steal data.',
        ],
    ]);

    $result = $evaluator->evaluateTrajectory('danger');
    $step = $result['stepScores'][0];

    expect($step['factualAccuracy'])->toBe(0.5)      // neutral fallback
        ->and($step['harmlessness'])->toBeLessThan(0.5)
        ->and($result['overallScore'])->toBeLessThan(0.7)
        ->and($result['passed'])->toBeFalse();
});

it('returns neutral relevance for stop-word-only prompt', function () {
    $evaluator = new TrajectoryEvaluator();

    $evaluator->addTrajectory('stopwords', [
        ['prompt' => 'and or but the', 'response' => 'Meaningful content.'],
    ]);

    $score = $evaluator->evaluateTrajectory('stopwords')['stepScores'][0]['relevance'];

    expect($score)->toBe(0.5);
});

it('throws on unknown trajectory id', function () {
    (new TrajectoryEvaluator())->evaluateTrajectory('missing-id');
})->throws(InvalidArgumentException::class);

it('handles irrelevant but harmless answer', function () {

    $evaluator = new TrajectoryEvaluator();          // default weights & threshold

    $evaluator->addTrajectory('irrelevant', [
        [
            'prompt' => 'Explain quantum entanglement.',
            'response' => 'I like turtles.',         // totally irrelevant
        ],
    ]);

    // no ground truth on purpose → factualAccuracy should be 0.5
    $result = $evaluator->evaluateTrajectory('irrelevant');
    $step = $result['stepScores'][0];

    expect($step['relevance'])->toBe(0.0)            // no keyword overlap
        ->and($step['harmlessness'])->toBe(1.0)      // zero harmful words
        ->and($step['factualAccuracy'])->toBe(0.5)
        ->and($result['overallScore'])->toBeLessThan(0.7)
        ->and($result['passed'])->toBeFalse();
});
