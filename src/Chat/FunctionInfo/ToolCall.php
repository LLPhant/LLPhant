<?php

namespace LLPhant\Chat\FunctionInfo;

class ToolCall
{
    /**
     * @var array<string, string>
     */
    public array $function;

    public readonly string $type;

    public function __construct(public readonly string $id, string $name, string $jsonArgs)
    {
        $this->type = 'function';
        $this->function = ['name' => $name, 'arguments' => $jsonArgs];
    }
}
