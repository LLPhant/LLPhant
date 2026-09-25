<?php

declare(strict_types=1);

namespace Tests\Unit\Chat\Function;

use LLPhant\Chat\FunctionInfo\FunctionFormatter;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use stdClass;
use Tests\Integration\Chat\MailerExample;

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
    ];

    expect(FunctionFormatter::formatOneFunctionToOpenAI($functionInfo))->toBe($expected);
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
    ];

    expect(FunctionFormatter::formatOneFunctionToOpenAI($functionInfo))->toBe($expected);
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
    ];

    expect(FunctionFormatter::formatOneFunctionToOpenAI($functionInfo))->toBe($expected);
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
    ];

    expect(FunctionFormatter::formatOneFunctionToOpenAI($functionInfo))->toBe($expected);
});

// Without the fix, empty parameters serialize properties as [] (JSON array) instead of {} (JSON object).
// Anthropic rejects this with: 400 invalid_request_error:
// tools.N.custom.input_schema.properties: Input should be an object
it('serializes empty tool properties as an object not an array', function () {
    $function = new FunctionInfo('no_args_tool', new stdClass(), 'A tool with no parameters', [], []);

    $anthropic = FunctionFormatter::formatFunctionsToAnthropic([$function]);
    $openai = FunctionFormatter::formatFunctionsToOpenAI([$function]);

    expect($anthropic[0]['input_schema']['properties'])->toEqual(new stdClass());
    expect(json_encode($anthropic[0]))->toContain('"properties":{}');

    expect($openai[0]['parameters']['properties'])->toEqual(new stdClass());
});

it('can format function info for Anthropic', function () {
    $subject = new Parameter('subject', 'string', 'the subject of the mail');
    $body = new Parameter('body', 'string', 'the body of the mail');
    $email = new Parameter('email', 'string', 'the email address');
    $label = new Parameter('label', 'string', 'the label of the email', ['normal', 'urgent']);

    $function = new FunctionInfo(
        'sendMail',
        new MailerExample(),
        'send a mail',
        [$subject, $body, $email, $label],
        [$subject, $body, $email, $label]
    );

    $formattedFunction = FunctionFormatter::formatFunctionsToAnthropic([$function]);

    $expected = <<<'JSON'
    {
        "name": "sendMail",
        "description": "send a mail",
        "input_schema": {
            "type": "object",
            "properties": {
                "subject": {
                    "type": "string",
                    "description": "the subject of the mail"
                },
                "body": {
                    "type": "string",
                    "description": "the body of the mail"
                },
                "email": {
                    "type": "string",
                    "description": "the email address"
                },
                "label": {
                    "type": "string",
                    "description": "the label of the email",
                    "enum": [
                        "normal",
                        "urgent"
                    ]
                }
            },
            "required": [
                "subject",
                "body",
                "email",
                "label"
            ]
        }
    }
    JSON;

    expect(json_encode($formattedFunction[0], JSON_PRETTY_PRINT))->toBe($expected);
});

it('can format function info with no parameters for Anthropic', function () {
    $function = new FunctionInfo(
        'getItemList',
        new MailerExample(),
        'Get a list of items from my warehouse',
        [],
        []
    );

    $formattedFunction = FunctionFormatter::formatFunctionsToAnthropic([$function]);

    $expected = <<<'JSON'
    {
        "name": "getItemList",
        "description": "Get a list of items from my warehouse",
        "input_schema": {
            "type": "object",
            "properties": {}
        }
    }
    JSON;

    expect(json_encode($formattedFunction[0], JSON_PRETTY_PRINT))->toBe($expected);
});
