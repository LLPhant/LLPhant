<?php

namespace LLPhant\Chat\CalledFunction;

use LLPhant\Chat\FunctionInfo\FunctionInfo;

class CalledFunction
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>|null  $extra_content
     */
    public function __construct(
        public FunctionInfo $definition,
        public array $arguments,
        public ?string $return,
        public ?string $tool_call_id = null,
        public ?array $extra_content = null
    ) {
    }
}
