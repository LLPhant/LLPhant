<?php

namespace LLPhant\Tool;

use LLPhant\Classification\FunctionInfo\FunctionBuilder;
use LLPhant\Classification\FunctionInfo\FunctionInfo;

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
