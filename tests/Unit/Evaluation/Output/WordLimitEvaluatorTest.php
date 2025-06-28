<?php

declare(strict_types=1);

namespace Tests\Unit\Evaluation\StringComparison;

use LLPhant\Evaluation\Output\WordLimitEvaluator;

it('can evaluate word limit is exceeded', function (): void {
    $output = "Lorizzle ipsum dolor sit fizzle, dizzle adipiscing fo shizzle. Nullam sapien own yo', mah nizzle volutpizzle, suscipizzle yippiyo, gravida vizzle, fo shizzle my nizzle. Pellentesque egizzle tortor. Fo shizzle erizzle. Rizzle at break it down dapibus pimpin' tempizzle shiz. Mauris gangster my shizz sizzle turpizzle. Vestibulum shut the shizzle up fizzle. Pellentesque eleifend rhoncizzle doggy. In hac that's the shizzle fo shizzle dictumst. Donec shizznit.";

    $results = (new WordLimitEvaluator())->setWordLimit(50)->evaluateText($output);

    expect($results->getResults())->toBe([
        'score' => 0,
        'error' => 'Generated 66 words is grater than limit of 50',
    ]);
});
