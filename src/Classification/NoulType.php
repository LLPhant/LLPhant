<?php

declare(strict_types=1);

namespace LLPhant\Classification;

class NoulType extends QuestionType
{
    public function __construct(string $instructions)
    {
        parent::__construct('noul', $instructions);
    }
}
