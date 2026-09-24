<?php

declare(strict_types=1);

namespace LLPhant\Classification;

interface ClassifierInterface
{
    /**
     * @param string $state
     * @param array<string, QuestionType> $questions
     * @return array <string, Answer>
     */
    function askQuestions(string $state, array $questions): array;
}
