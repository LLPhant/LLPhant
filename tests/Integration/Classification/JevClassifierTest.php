<?php

declare(strict_types=1);

namespace Tests\Integration\Classification;

use LLPhant\Classification\ChoiceAnswer;
use LLPhant\Classification\ChoiceType;
use LLPhant\Classification\JevClassifier;
use LLPhant\Classification\NoulAnswer;
use LLPhant\Classification\NoulCriteria;
use LLPhant\Classification\NoulType;
use LLPhant\Classification\ScoreAnswer;
use LLPhant\Classification\ScoreCriteria;
use LLPhant\Classification\ScoreType;

it('can generate a noul answer with no criteria', function () {
    $chat = new JevClassifier();
    $questions = [
        'is_urgent' => new NoulType('Does this convey urgency?'),
    ];
    $response = $chat->askQuestions('Help! My payouts have been failing for 3 days.', $questions);

    $expected = [
        'is_urgent' => new NoulAnswer(0.95),
    ];

    expect($response['is_urgent']->score)->toBe($expected['is_urgent']->score);
    expect($response['is_urgent']->inputTokens)->toBeGreaterThan(0);
    expect($response['is_urgent']->outputTokens)->toBeGreaterThan(0);
});

it('can generate a noul answer with some criteria', function () {
    $chat = new JevClassifier();
    $questions = [
        'is_urgent' => new NoulType(
            'Does this convey urgency?',
            new NoulCriteria('Explicitly time-sensitive', 'No urgency expressed')),
    ];
    $response = $chat->askQuestions('Help! My payouts have been failing for 3 days.', $questions);

    $expected = [
        'is_urgent' => new NoulAnswer(0.95),
    ];

    expect($response['is_urgent']->score)->toBe($expected['is_urgent']->score);
    expect($response['is_urgent']->inputTokens)->toBeGreaterThan(0);
    expect($response['is_urgent']->outputTokens)->toBeGreaterThan(0);
});

it('can generate a choice answer with some criteria', function () {
    $chat = new JevClassifier();
    $questions = [
        'department' => new ChoiceType(
            'Which team should handle this?',
            [
                'billing' => 'Payments, invoicing, refunds',
                'technical' => 'Bugs, outages, integrations',
                'sales' => 'Pricing, upgrades, new accounts',
            ]
        ),
    ];
    $response = $chat->askQuestions('Help! My payouts have been failing for 3 days.', $questions);

    $expected = [
        'department' => new ChoiceAnswer(
            choice: 'billing',
            probabilities: [
                'billing' => 0.88,
                'technical' => 0.12,
                'sales' => 0.0,
            ],
            confidence: 0.81
        ),
    ];

    expect($expected['department']->isSimilarTo($response['department']))->toBeTrue('Got a different response: '.json_encode($response));
    expect($response['department']->inputTokens)->toBeGreaterThan(0);
    expect($response['department']->outputTokens)->toBeGreaterThan(0);
});

it('can generate a score answer with some criteria', function () {
    $chat = new JevClassifier();
    $questions = [
        'frustration' => new ScoreType(
            'How frustrated is the customer?',
            new ScoreCriteria(['Calm', 'Frustrated', 'Very angry'])
        ),
    ];
    $response = $chat->askQuestions('Help! My payouts have been failing for 3 days.', $questions);

    $expected = [
        'frustration' => new ScoreAnswer(
            score: 1.05,
            legend: [
                '0' => 'Calm',
                '1' => 'Frustrated',
                '2' => 'Very angry',
            ],
            probabilities: [
                '0' => 0.0,
                '1' => 0.95,
                '2' => 0.05,
            ],
            confidence: 0.92,
        ),
    ];

    expect($expected['frustration']->isSimilarTo($response['frustration']))->toBeTrue('Got a different response: '.json_encode($response));
    expect($response['frustration']->inputTokens)->toBeGreaterThan(0);
    expect($response['frustration']->outputTokens)->toBeGreaterThan(0);
});
