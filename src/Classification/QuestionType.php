<?php

declare(strict_types=1);

namespace LLPhant\Classification;

abstract class QuestionType
{
    public function __construct(
        public readonly string $type,
        public readonly string $instructions,
    ) {
    }
}
