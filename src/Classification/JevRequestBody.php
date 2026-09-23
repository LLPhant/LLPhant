<?php

namespace LLPhant\Classification;


class JevRequestBody
{

    /**
     * @param string $model
     * @param string $state
     * @param QuestionType[] $questions
     */
    public function __construct(
        private readonly string $model,
        private readonly string $state,
        private readonly array  $questions)
    {
    }

    /**
     * @throws \JsonException
     */
    public function toJSON(bool $prettyPrint = false): string
    {
        $data = [
            'state' => $this->state,
            'model' => $this->model,
            'questions' => $this->questions,
        ];

        $flags = JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return \json_encode($data, $flags);
    }
}
