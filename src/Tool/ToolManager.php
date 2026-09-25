<?php

namespace LLPhant\Tool;

use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Chat\FunctionInfo\FunctionInfo;

class ToolManager
{
    /**
     * @return FunctionInfo[]
     */
    public static function getAllToolsFunction(): array
    {
        $searchApi = new SerpApiSearch();
        $function = FunctionBuilder::buildFunctionInfo($searchApi, 'search');

        return [$function];
    }
}
