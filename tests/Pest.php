<?php

function fixture(string $name): array
{
    $content = file_get_contents(__DIR__."/Fixtures/$name.json");

    if (! $content) {
        throw new InvalidArgumentException(
            "Cannot find fixture: [$name] at Fixtures/$name.json",
        );
    }

    return json_decode($content, true);
}

expect()->extend('toMatchJson', function (string $actual) {
    return expect(json_decode($this->value, true))
        ->toEqual(json_decode($actual, true));
});

expect()->extend('toBeNearTo', function (float $actual, float $epsilon) {
    return expect(abs($this->value - $actual))->toBeLessThanOrEqual($epsilon, "Values are not near: {$this->value}, {$actual}");
});
