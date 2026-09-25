<?php

namespace LLPhant\Classification;

abstract class Answer
{
    public function __construct(
        public readonly string $type,
    ) {
    }
}
