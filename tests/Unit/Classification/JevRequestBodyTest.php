<?php

namespace Tests\Unit\Classification;

use LLPhant\Classification\JevRequestBody;
use LLPhant\Classification\NoulType;

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
            'is_urgent' => new NoulType('Does this convey urgency?')
        ]
    );

    expect($jevRequestBody->toJSON(prettyPrint: true))->toBe($expectedJson);

});
