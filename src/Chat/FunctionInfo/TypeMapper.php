<?php

namespace LLPhant\Chat\FunctionInfo;

use InvalidArgumentException;
use ReflectionNamedType;

class TypeMapper
{
    /**
     * @var array<string, string>
     */
    private const MAPPING = [
        'string' => 'string',
        'int' => 'integer',
        'float' => 'number',
        'bool' => 'boolean',
        'array' => 'array',
    ];

    public static function mapPhpTypeToJsonSchemaType(ReflectionNamedType $reflectionType): string
    {
        $name = $reflectionType->getName();

        if (! isset(self::MAPPING[$name])) {
            throw new InvalidArgumentException("Unsupported type: {$name}");
        }

        return self::MAPPING[$name];
    }
}
