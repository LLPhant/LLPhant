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

    public static function fromFunctionInfo(FunctionInfo $functionInfo): self
    {
        return new self($functionInfo->getToolCallId() ?? '', $functionInfo->name, $functionInfo->jsonArgs);
    }

    /**
     * @param  FunctionInfo[]  $functionInfos
     * @return ToolCall[]
     */
    public static function fromFunctionInfos(array $functionInfos): array
    {
        return \array_map('LLPhant\Chat\FunctionInfo\ToolCall::fromFunctionInfo', $functionInfos);
    }
}
