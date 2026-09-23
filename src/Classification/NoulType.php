<?php

declare(strict_types=1);

namespace LLPhant\Classification;

use JsonSerializable;

class NoulType extends QuestionType
{
    public function __construct(string $instructions, public readonly ?NoulCriteria $criteria = null)
    {
        parent::__construct('noul', $instructions);
    }
}
