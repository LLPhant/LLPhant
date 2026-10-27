<?php

declare(strict_types=1);

namespace LLPhant\Classification;

interface ClassifierInterface
{
    /**
     * @param  array<string, QuestionType>  $questions
     * @return array <string, Answer>
     */
    public function askQuestions(string $state, array $questions): array;
}
