<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class ScoreType extends QuestionType
{
    /**
     * @param  ScoreCriteria|array<int, string>  $criteria
     */
    public function __construct(
        string $instructions,
        ScoreCriteria|array $criteria
    ) {
        parent::__construct('score', $instructions);

        $this->criteria = $criteria instanceof ScoreCriteria ? $criteria->levels : $criteria;
    }

    /**
     * @var array<int, string>
     */
    public readonly array $criteria;
}
