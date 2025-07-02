<?php

namespace Tests\Unit\Chat;

use LLPhant\Chat\Anthropic\AnthropicImage;
use LLPhant\Chat\Anthropic\AnthropicImageType;
use LLPhant\Chat\Anthropic\AnthropicMessage;
use LLPhant\Chat\Anthropic\AnthropicVisionMessage;

it('generates a correct tool result message for Anthropic', function () {

    $expectedJson = <<<'JSON'
    {
        "role": "user",
        "content": [
            {
                "type": "tool_result",
                "tool_use_id": "toolu_01A09q90qw90lq917835lq9",
                "content": "15 degrees"
            }
        ]
    }
    JSON;

    $toolsOutput = ['toolu_01A09q90qw90lq917835lq9' => '15 degrees'];

    expect(\json_encode(AnthropicMessage::toolResultMessage($toolsOutput), JSON_PRETTY_PRINT))->toBe($expectedJson);
});

it('generates a correct assistant answer message for Anthropic', function () {

    $expectedJson = <<<'JSON'
    {
        "role": "assistant",
        "content": [
            {
                "type": "text",
                "text": "<thinking>I need to call the get_weather function, and the user wants SF, which is likely San Francisco, CA.<\/thinking>"
            },
            {
                "type": "tool_use",
                "id": "toolu_01A09q90qw90lq917835lq9",
                "name": "get_weather",
                "input": {
                    "location": "San Francisco, CA",
                    "unit": "celsius"
                }
            }
        ]
    }
    JSON;

    $assistantAnswer = [
        ['type' => 'text', 'text' => '<thinking>I need to call the get_weather function, and the user wants SF, which is likely San Francisco, CA.</thinking>'],
        ['type' => 'tool_use', 'id' => 'toolu_01A09q90qw90lq917835lq9', 'name' => 'get_weather', 'input' => ['location' => 'San Francisco, CA', 'unit' => 'celsius']],
    ];

    expect(\json_encode(AnthropicMessage::fromAssistantAnswer($assistantAnswer), JSON_PRETTY_PRINT))->toBe($expectedJson);
});

it('generates a correct vison message for Anthropic', function () {

    $expectedJson = <<<'JSON'
    {
        "role": "user",
        "content": [
            {
                "type": "image",
                "source": {
                    "type": "base64",
                    "media_type": "image\/jpeg",
                    "data": "\/9j\/4AAQSkZJRgABAQAAAQABAAD\/7QBwUGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAFMcAVoAAxslRxwCAAACAAAcAnQAP8KpIFZpY0ZyZWVkb21pbmQgLSBodHRwOi8vd3d3LnJlZGJ1YmJsZS5jb20vcGVvcGxlL3ZpY2ZyZWVkb21pbgD\/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT\/wgALCAPoAu4BASIA\/8QAHQABAAICAwEBAAAAAAAAAAAAAAcIBQYDBAkCAf\/aAAgBAQAAAAG0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1+qmpAAAAAAAAAAAAAAB9SfaChmFmEAAAAAAAAAAAAAAOOAbD1kvNNgAAAADq6tuY4Y7irS5eg3RAHZnSy\/eAAAAACn9ceG9szgAAAAIv37AVw2SWtk62pdiguzdyPwCylos8AAAAAKiVmXtmcAAAADWO3VXdZ\/hLtVT\/exYqoXWABIPoP3AAAAAColZl7ZnAAAAA1vV5LormrHxhAkbu70gAE23nAAAAAKiVmXtmcAAAAEORPO\/HxUL\/ZTifoAAB9em+bAAAAAVErMvbM4AAAAcXTyMJVUx\/"
                }
            },
            {
                "type": "text",
                "text": "Describe the image"
            }
        ]
    }
    JSON;

    $visionMessage = new AnthropicVisionMessage([
        new AnthropicImage(AnthropicImageType::JPEG, '/9j/4AAQSkZJRgABAQAAAQABAAD/7QBwUGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAFMcAVoAAxslRxwCAAACAAAcAnQAP8KpIFZpY0ZyZWVkb21pbmQgLSBodHRwOi8vd3d3LnJlZGJ1YmJsZS5jb20vcGVvcGxlL3ZpY2ZyZWVkb21pbgD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/wgALCAPoAu4BASIA/8QAHQABAAICAwEBAAAAAAAAAAAAAAcIBQYDBAkCAf/aAAgBAQAAAAG0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1+qmpAAAAAAAAAAAAAAB9SfaChmFmEAAAAAAAAAAAAAAOOAbD1kvNNgAAAADq6tuY4Y7irS5eg3RAHZnSy/eAAAAACn9ceG9szgAAAAIv37AVw2SWtk62pdiguzdyPwCylos8AAAAAKiVmXtmcAAAADWO3VXdZ/hLtVT/exYqoXWABIPoP3AAAAAColZl7ZnAAAAA1vV5LormrHxhAkbu70gAE23nAAAAAKiVmXtmcAAAAEORPO/HxUL/ZTifoAAB9em+bAAAAAVErMvbM4AAAAcXTyMJVUx/'),
    ]);

    expect(\json_encode($visionMessage, JSON_PRETTY_PRINT))->toBe($expectedJson);
});

it('blocks wrong contents for an Anthropic vison message', function () {
    new AnthropicVisionMessage([
        new AnthropicImage(AnthropicImageType::JPEG, 'This is not a valid base64 image'),
    ]);
})->throws(\InvalidArgumentException::class);
