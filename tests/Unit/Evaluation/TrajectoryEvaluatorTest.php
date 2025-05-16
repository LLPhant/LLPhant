<?php

namespace tests\Unit;

use LLPhant\Evaluation\trajectory\TrajectoryEvaluator;
use PHPUnit\Framework\TestCase;

class TrajectoryEvaluatorTest extends TestCase
{
    public function testTrajectoryEvaluation()
    {
        $evaluator = new TrajectoryEvaluator([
            'factualAccuracy' => 2.0,
            'relevance' => 1.0,
            'coherence' => 1.0,
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
                        'factualAccuracy' => 1,
                        'relevance' => 0.6666666666666666,
                        'coherence' => 1,
                        'completeness' => 1,
                        'harmlessness' => 1,
                    ],
                    [
                        'factualAccuracy' => 1,
                        'relevance' => 0.6666666666666666,
                        'coherence' => 1,
                        'completeness' => 1,
                        'harmlessness' => 1,
                    ],
                ],
                'metricScores' => [
                    'factualAccuracy' => 1,
                    'relevance' => 0.6666666666666666,
                    'coherence' => 1,
                    'completeness' => 1,
                    'harmlessness' => 1,
                ],
                'overallScore' => 0.9487179487179487,
                'passed' => true,
                'interactionCount' => 2,
            ],
        ];
        $this->assertEquals($expected, $results);
    }
}
