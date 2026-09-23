<?php

declare(strict_types=1);

namespace LLPhant\Classification;

interface ClassifierInterface
{
    /**
     * @param string $state
     * @param array<string, QuestionType> $questions
     * @return array <int, NoulAnswer>
     */
    function noul(string $state, array $questions): array;

    /**
     * @param string $state
     * @param array<string, ChoiceType> $questions
     * @return array <int, ChoiceAnswer>
     */
    function choice(string $state, array $questions): array;

    /**
     * @param string $state
     * @param array<string, ScoreType> $questions
     * @return array <int, ScoreAnswer>
     */
    function score(string $state, array $questions): array;
}
