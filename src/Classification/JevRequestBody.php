<?php

namespace LLPhant\Classification;

class JevRequestBody
{
    /**
     * @param  QuestionType[]  $questions
     */
    public function __construct(
        private readonly string $model,
        private readonly string $state,
        private readonly array $questions)
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

        $flags = JSON_THROW_ON_ERROR;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return \json_encode($this->removeNulls($data), $flags);
    }

    public static function removeNulls(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_filter(
                array_map(
                    [self::class, 'removeNulls'],
                    $value
                ),
                static fn (mixed $x): bool => $x !== null
            );
        }

        if (is_object($value)) {
            $result = [];

            foreach (get_object_vars($value) as $property => $propertyValue) {
                if ($propertyValue !== null) {
                    $result[$property] = self::removeNulls($propertyValue);
                }
            }

            return $result;
        }

        return $value;
    }
}
