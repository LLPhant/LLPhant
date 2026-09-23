<?php

namespace LLPhant\Classification\CalledFunction;

use LLPhant\Classification\FunctionInfo\FunctionInfo;

class CalledFunction
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(public FunctionInfo $definition, public array $arguments, public ?string $return, public ?string $tool_call_id = null)
    {
    }
}
