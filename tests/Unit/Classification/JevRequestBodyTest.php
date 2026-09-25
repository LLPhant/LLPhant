<?php

namespace Tests\Unit\Classification;

use LLPhant\Classification\JevRequestBody;
use LLPhant\Classification\NoulType;
use LLPhant\Classification\ScoreCriteria;
use LLPhant\Classification\ScoreType;

it('can create a valid Jev request JSON body', function () {
    $expectedJson = <<<'JSON'
    {
        "state": "Help! My payouts have been failing for 3 days.",
        "model": "jev-latest",
        "questions": {
            "is_urgent": {
                "type": "noul",
                "instructions": "Does this convey urgency?"
            }
        }
    }
    JSON;

    $jevRequestBody = new JevRequestBody(
        model: 'jev-latest',
        state: 'Help! My payouts have been failing for 3 days.',
        questions: [
            'is_urgent' => new NoulType('Does this convey urgency?'),
        ]
    );

    expect($jevRequestBody->toJSON(prettyPrint: true))->toBe($expectedJson);

});

it('can create a valid Jev request JSON body with score criteria', function () {
    $expectedJson = <<<'JSON'
    {
        "state": "Help! My payouts have been failing for 3 days.",
        "model": "jev-latest",
        "questions": {
            "frustration": {
                "type": "score",
                "instructions": "How frustrated is the customer?",
                "criteria": [
                    "Calm",
                    "Frustrated",
                    "Very angry"
                ]
            }
        }
    }
    JSON;

    $jevRequestBody = new JevRequestBody(
        model: 'jev-latest',
        state: 'Help! My payouts have been failing for 3 days.',
        questions: [
            'frustration' => new ScoreType(
                'How frustrated is the customer?',
                new ScoreCriteria(['Calm', 'Frustrated', 'Very angry'])
            ),
        ]
    );

    expect($jevRequestBody->toJSON(prettyPrint: true))->toBe($expectedJson);
});
