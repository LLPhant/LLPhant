<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class NoulAnswer extends Answer
{
    const MAX_DIFFERENCE = 0.05;

    public function __construct(
        public readonly float $score,
        int $inputTokens = 0,
        int $outputTokens = 0
    ) {
        parent::__construct('noul', $inputTokens, $outputTokens);
    }

    public function isTrue(float $minTrueScore = 0.95): bool
    {
        if (! is_finite($minTrueScore) || $minTrueScore < 0.0 || $minTrueScore > 1.0) {
            throw new \InvalidArgumentException('The minimum true score must be between 0 and 1.');
        }

        return $this->score >= $minTrueScore;
    }

    public function isSimilarTo(NoulAnswer $answer): bool
    {
        if (abs($answer->score - $this->score) > self::MAX_DIFFERENCE) {
            return false;
        }

        return true;
    }
}
