<?php

declare(strict_types=1);

namespace Tests\Unit\Chat\Function;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\FunctionInfo\ToolFormatter;

it('can format function info with basic types to OpenAI format', function () {
    $parameters = [
        new Parameter('param1', 'string', 'description1'),
        new Parameter('param2', 'integer', 'description2', ['enum1', 'enum2'], 'format1'),
    ];

    $requiredParameters = [
        new Parameter('param1', 'string', 'description1'),
    ];

    $functionInfo = new FunctionInfo('testFunction', 'TestClass', 'testDescription', $parameters, $requiredParameters);

    $expected = [
        'type' => 'function',
        'function' => [
            'name' => 'testFunction',
            'description' => 'testDescription',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'description1',
                    ],
                    'param2' => [
                        'type' => 'integer',
                        'description' => 'description2',
                        'enum' => ['enum1', 'enum2'],
                        'format' => 'format1',
                    ],
                ],
                'required' => ['param1'],
            ],
        ],
    ];

    expect(ToolFormatter::formatOneToolToOpenAI($functionInfo))->toEqual($expected);
});

it('can format function with object parameter to OpenAI format', function () {
    $nameTask = new Parameter('name', 'string', 'name of the task');
    $descriptionTask = new Parameter('description', 'string', 'description of the task');
    $taskObject = new Parameter('task', 'object', 'one task', [], null, [$nameTask, $descriptionTask]);

    $parameters = [
        $taskObject,
    ];

    $functionInfo = new FunctionInfo('testFunction', 'TestClass', 'testDescription', $parameters, []);

    $expected = [
        'type' => 'function',
        'function' => [
            'name' => 'testFunction',
            'description' => 'testDescription',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'task' => [
                        'type' => 'object',
                        'description' => 'one task',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'name of the task',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'description of the task',
                            ],
                        ],
                    ],
                ],
                'required' => [],
            ],
        ],
    ];

    expect(ToolFormatter::formatOneToolToOpenAI($functionInfo))->toEqual($expected);
});

it('can format function info with simple array parameter to OpenAI format', function () {
    $stringArray = new Parameter('simpleArray', 'array', 'd4', [], null, 'string');

    $parameters = [
        $stringArray,
    ];

    $requiredParameters = [
    ];

    $functionInfo = new FunctionInfo('testFunction', 'TestClass', 'testDescription', $parameters, $requiredParameters);

    $expected = [
        'type' => 'function',
        'function' => [
            'name' => 'testFunction',
            'description' => 'testDescription',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'simpleArray' => [
                        'type' => 'array',
                        'description' => 'd4',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'required' => [],
            ],
        ],
    ];

    expect(ToolFormatter::formatOneToolToOpenAI($functionInfo))->toBe($expected);
});

it('can format function info with array of objects types to OpenAI format', function () {
    $nameTask = new Parameter('name', 'string', 'name of the task');
    $descriptionTask = new Parameter('description', 'string', 'description of the task');
    $array = new Parameter('tasks', 'array', 'tasks to be added to the list of tasks to be completed', [], null, [$nameTask, $descriptionTask]);

    $parameters = [
        $array,
    ];

    $requiredParameters = [
    ];

    $functionInfo = new FunctionInfo('testFunction', 'TestClass', 'testDescription', $parameters, $requiredParameters);

    $expected = [
        'type' => 'function',
        'function' => [
            'name' => 'testFunction',
            'description' => 'testDescription',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'tasks' => [
                        'type' => 'array',
                        'description' => 'tasks to be added to the list of tasks to be completed',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'description' => 'name of the task',
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'description of the task',
                                ],
                            ],
                        ],
                    ],
                ],
                'required' => [],
            ],
        ],
    ];

    expect(ToolFormatter::formatOneToolToOpenAI($functionInfo))->toBe($expected);
});

it('can format function info with no parameters to OpenAI format', function () {
    $functionInfo = new FunctionInfo('testFunction', 'TestClass', 'testDescription', [], []);

    $expected = [
        'type' => 'function',
        'function' => [
            'name' => 'testFunction',
            'description' => 'testDescription',
            'parameters' => [
                'type' => 'object',
                'properties' => new \stdClass(),
                'required' => [],
            ],
        ],
    ];

    expect(ToolFormatter::formatOneToolToOpenAI($functionInfo))->toEqual($expected);
});
