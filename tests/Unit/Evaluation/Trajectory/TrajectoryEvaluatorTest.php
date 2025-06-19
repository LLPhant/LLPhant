<?php

namespace tests\Unit\Evaluation\Trajectory;

use InvalidArgumentException;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\Trajectory\TrajectoryEvaluator;

it('can generate trajectory evaluation', function (): void {
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

it('flags harmful answer and handles missing ground truth', function (): void {
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

it('returns neutral relevance for stop-word-only prompt', function (): void {
    $evaluator = new TrajectoryEvaluator();

    $evaluator->addTrajectory('stopwords', [
        ['prompt' => 'and or but the', 'response' => 'Meaningful content.'],
    ]);

    $score = $evaluator->evaluateTrajectory('stopwords')['stepScores'][0]['relevance'];

    expect($score)->toBe(0.5);
});

it('throws on unknown trajectory id', function (): void {
    (new TrajectoryEvaluator())->evaluateTrajectory('missing-id');
})->throws(InvalidArgumentException::class);

it('handles irrelevant but harmless answer', function (): void {

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

it('can compute trajectory metrics from text', function (): void {
    $evaluator = new TrajectoryEvaluator([
        'factualAccuracy' => 2.0,
        'relevance' => 1.0,
        'completeness' => 1.0,
        'harmlessness' => 1.5,
    ]);

    $evaluator->addGroundTruth('task1', [
        ['Paris', 'capital', 'France'],
        ['Paris', 'population', '2.2 million'],
    ]);

    $results = $evaluator->evaluateText('The capital of France is Paris.', 'What is the capital of France?');
    $expected = [
        'task1_trajectoryId' => 'task1',
        'task1_stepScores_0_factualAccuracy' => 1.0,
        'task1_stepScores_0_relevance' => 1.0,
        'task1_stepScores_0_completeness' => 1.0,
        'task1_stepScores_0_harmlessness' => 1.0,
        'task1_metricScores_factualAccuracy' => 1.0,
        'task1_metricScores_relevance' => 1.0,
        'task1_metricScores_completeness' => 1.0,
        'task1_metricScores_harmlessness' => 1.0,
        'task1_overallScore' => 1.0,
        'task1_passed' => true,
        'task1_interactionCount' => 1,
    ];
    expect($results->getResults())->toBe($expected);
});

it('can count all string comparison scores from messages', function (): void {

    $evaluator = new TrajectoryEvaluator([
        'factualAccuracy' => 2.0,
        'relevance' => 1.0,
        'completeness' => 1.0,
        'harmlessness' => 1.5,
    ]);

    $evaluator->addGroundTruth('task1', [
        ['Paris', 'capital', 'France'],
        ['Paris', 'population', '2.2 million'],
    ]);

    $message1 = Message::user('What is the capital of France?');
    $response1 = Message::assistant('The capital of France is Paris.');

    $message2 = Message::user('What is the population of Paris?');
    $response2 = Message::assistant('Paris has a population of approximately 2.2 million people in the city proper.');

    $results = $evaluator->evaluateMessages([
        $message1,
        $response1,
        $message2,
        $response2,
    ]);
    $expected = [
        'task1_trajectoryId' => 'task1',
        'task1_stepScores_0_factualAccuracy' => 0.0,
        'task1_stepScores_0_relevance' => 0.0,
        'task1_stepScores_0_completeness' => 0.0,
        'task1_stepScores_0_harmlessness' => 1.0,
        'task1_stepScores_1_factualAccuracy' => 0.0,
        'task1_stepScores_1_relevance' => 0.0,
        'task1_stepScores_1_completeness' => 0.0,
        'task1_stepScores_1_harmlessness' => 1.0,
        'task1_metricScores_factualAccuracy' => 0.0,
        'task1_metricScores_relevance' => 0.0,
        'task1_metricScores_completeness' => 0.0,
        'task1_metricScores_harmlessness' => 1.0,
        'task1_overallScore' => 0.27,
        'task1_passed' => false,
        'task1_interactionCount' => 2,
    ];
    expect($results->getResults())->toBe($expected);
});
