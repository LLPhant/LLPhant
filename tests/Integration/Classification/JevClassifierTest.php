<?php

declare(strict_types=1);

namespace Tests\Integration\Classification;

use LLPhant\Classification\Anthropic\AnthropicImage;
use LLPhant\Classification\Anthropic\AnthropicImageType;
use LLPhant\Classification\Anthropic\AnthropicVisionMessage;
use LLPhant\Classification\AnthropicChat;
use LLPhant\Classification\FunctionInfo\FunctionInfo;
use LLPhant\Classification\FunctionInfo\Parameter;
use LLPhant\Classification\JevClassifier;
use LLPhant\Classification\NoulAnswer;
use LLPhant\Classification\NoulType;

it('can generate some noul answer with no criteria', function () {
    $chat = new JevClassifier();
    $questions = [
        'is_urgent' => new NoulType('Does this convey urgency?')
    ];
    $response = $chat->askQuestions('Help! My payouts have been failing for 3 days.', $questions);

    $expected = [
        'is_urgent' => new NoulAnswer(0.95)
    ];

    expect($response)->toEqual($expected);
});

