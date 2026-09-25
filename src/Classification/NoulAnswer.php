<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class NoulAnswer extends Answer
{
    public function __construct(
        public readonly float $score,
        int $inputTokens = 0,
        int $outputTokens = 0
    ) {
        parent::__construct('noul', $inputTokens, $outputTokens);
    }
}
