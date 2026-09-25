<?php

namespace LLPhant\Chat\FunctionInfo;

class Parameter
{
    /**
     * @param  mixed[]  $enum
     * @param  mixed[]|null  $itemsOrProperties
     */
    public function __construct(public string $name, public string $type, public string $description, public array $enum = [], public ?string $format = null, public array|string|null $itemsOrProperties = null)
    {
    }
}
