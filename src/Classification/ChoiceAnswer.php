<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class ChoiceAnswer extends Answer
{
    const MAX_DIFFERENCE = 0.05;

    /**
     * @param  array<string, float>  $probabilities
     */
    public function __construct(
        public readonly string $choice,
        public readonly array $probabilities,
        public readonly float $confidence,
        int $inputTokens = 0,
        int $outputTokens = 0)
    {
        parent::__construct('choice', $inputTokens, $outputTokens);
    }

    public function isSimilarTo(ChoiceAnswer $answer): bool
    {
        if ($answer->choice !== $this->choice) {
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
