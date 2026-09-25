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
        public readonly float $confidence)
    {
        parent::__construct('choice');
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
            if (abs($value - $answer->probabilities[$key]) > self::MAX_DIFFERENCE) {
                return false;
            }
        }

        return true;
    }
}
