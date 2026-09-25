<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class ScoreCriteria
{
    /**
     * @param  array<int, string>  $levels
     */
    public function __construct(public readonly array $levels)
    {
    }
}
