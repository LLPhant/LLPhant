<?php

namespace LLPhant\Chat\FunctionInfo;

use ReflectionMethod;
use ReflectionNamedType;

class FunctionBuilder
{
    public static function buildFunctionInfo(object $instance, string $name): FunctionInfo
    {
        $reflection = new ReflectionMethod($instance::class, $name);
        $docComment = $reflection->getDocComment() ?: '';
        $params = $reflection->getParameters();

        $parametersDescriptions = [];
        preg_match_all('/@param.+?\$(\w+)\s+(.+)/', $docComment, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $paramName = $match[1];
            $paramDescription = trim($match[2]);
            $parametersDescriptions[$paramName] = $paramDescription;
        }

        $parameters = [];
        $requiredParameters = [];

        foreach ($params as $param) {
            /** @var ReflectionNamedType $reflectionType */
            $reflectionType = $param->getType();

            $parameterName = $param->getName();
            $newParameter = new Parameter($parameterName, TypeMapper::mapPhpTypeToJsonSchemaType($reflectionType), $parametersDescriptions[$parameterName] ?? '');

            if ($newParameter->type === 'array') {
                $newParameter->itemsOrProperties = self::getArrayType($reflection->getDocComment() ?: '', $param->getName());
            }

            $parameters[] = $newParameter;
            if (! $param->isOptional()) {
                $requiredParameters[] = $newParameter;
            }
        }

        // Remove PHPDoc annotations and get only the description
        $functionDescription = preg_replace('/\s*\* @.*/', '', $docComment);
        $functionDescription = trim(str_replace(['/**', '*/', '*'], '', $functionDescription ?? ''));

        return new FunctionInfo($name, $instance, $functionDescription, $parameters, $requiredParameters);
    }

    private static function getArrayType(string $doc, string $paramName): ?string
    {
        // Use a regex to find the parameter type
        $pattern = "/@param\s+([a-zA-Z0-9_|\\\[\]]+)\s+\\$".$paramName.'/';
        if (preg_match($pattern, $doc, $matches)) {
            // If the type is an array type (e.g., string[]), return the type without the brackets
            return preg_replace('/\[\]$/', '', $matches[1]);
        }

        // If the parameter was not found, return null
        return null;
    }
}
