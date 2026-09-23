<?php

namespace LLPhant\Classification;


use JsonSerializable;

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
                static fn ($value) => $value !== null
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
