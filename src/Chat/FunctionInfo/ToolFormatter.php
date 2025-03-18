<?php

namespace LLPhant\Chat\FunctionInfo;

class ToolFormatter
{
    /**
     * @param  FunctionInfo[]  $functions
     * @return mixed[]
     */
    public static function formatFunctionsToOpenAITools(array $functions): array
    {
        if ($functions === []) {
            return [];
        }

        $toolsOpenAI = [];
        foreach ($functions as $function) {
            $toolsOpenAI[] = self::formatOneToolToOpenAI($function);
        }

        return $toolsOpenAI;
    }

    /**
     * @return mixed[]
     *
     * @throws \Exception
     */
    public static function formatOneToolToOpenAI(FunctionInfo $functionInfo): array
    {
        $parametersOpenAI = [];
        foreach ($functionInfo->parameters as $parameter) {
            $param = FunctionFormatter::formatParameter($parameter);
            $parametersOpenAI[$parameter->name] = $param;
        }

        if ($parametersOpenAI === []) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $functionInfo->name,
                    'description' => $functionInfo->description,
                    'parameters' => new \stdClass(),
                ],
            ];
        }

        $requiredParametersOpenAI = [];
        foreach ($functionInfo->requiredParameters as $requiredParameter) {
            $requiredParametersOpenAI[] = $requiredParameter->name;
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $functionInfo->name,
                'description' => $functionInfo->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $parametersOpenAI,
                    'required' => $requiredParametersOpenAI,
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, function: array{name: string}}|null
     */
    public static function formatToolChoice(?FunctionInfo $requiredFunction): ?array
    {
        if (! $requiredFunction instanceof FunctionInfo) {
            return null;
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $requiredFunction->name,
            ],
        ];
    }
}
