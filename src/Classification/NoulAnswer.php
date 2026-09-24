<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class NoulAnswer extends Answer
{
    public function __construct(public readonly float $score)
    {
        parent::__construct('noul');
    }
}
