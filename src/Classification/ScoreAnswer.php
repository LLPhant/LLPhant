<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class ScoreAnswer extends Answer
{
    const MAX_DIFFERENCE = 0.05;

    /**
     * @param  array<string, string>  $legend
     * @param  array<string, float>  $probabilities
     */
    public function __construct(
        public readonly float $score,
        public readonly array $legend,
        public readonly array $probabilities,
        public readonly float $confidence,
        int $inputTokens = 0,
        int $outputTokens = 0,
    ) {
        parent::__construct('score', $inputTokens, $outputTokens);
    }

    public function isSimilarTo(ScoreAnswer $answer): bool
    {
        if (abs($answer->score - $this->score) > self::MAX_DIFFERENCE) {
            return false;
        }

        if ($answer->legend !== $this->legend) {
            return false;
        }

        if (abs($answer->confidence - $this->confidence) > self::MAX_DIFFERENCE) {
            return false;
        }

        if (count($answer->probabilities) !== count($this->probabilities)) {
            return false;
        }

        foreach ($this->probabilities as $key => $value) {
            if (! isset($answer->probabilities[$key])) {
                return false;
            }

            if (abs($value - $answer->probabilities[$key]) > self::MAX_DIFFERENCE) {
                return false;
            }
        }

        return true;
    }
}
