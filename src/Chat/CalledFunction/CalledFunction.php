<?php

namespace LLPhant\Chat\CalledFunction;

use LLPhant\Chat\FunctionInfo\FunctionInfo;

class CalledFunction
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(public FunctionInfo $definition, public array $arguments, public ?string $return, public ?string $tool_call_id = null)
    {
    }
}
