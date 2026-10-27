<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class ChoiceType extends QuestionType
{
    /**
     * @param  array<string, string>  $criteria
     */
    public function __construct(string $instructions,
        public readonly array $criteria)
    {
        parent::__construct('choice', $instructions);
    }
}
