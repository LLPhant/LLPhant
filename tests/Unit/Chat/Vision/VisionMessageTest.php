<?php

namespace Tests\Unit\Chat\Vision;

use LLPhant\Chat\Vision\ImageQuality;
use LLPhant\Chat\Vision\ImageSource;
use LLPhant\Chat\Vision\VisionMessage;

it('generates a correct user message for OpenAI', function () {

    $expectedJson = <<<'JSON'
    {
        "role": "user",
        "content": [
            {
                "type": "text",
                "text": "What are in these images? Is there any difference between them?"
            },
            {
                "type": "image_url",
                "image_url": {
                    "url": "https:\/\/example.test\/image.jpg",
                    "detail": "auto"
                }
            },
            {
                "type": "image_url",
                "image_url": {
                    "url": "data:image\/jpeg;base64,\/9j\/4AAQSkZJRgABAQEASABIAAD\/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT\/wAALCAABAAEBAREA\/8QAFAABAAAAAAAAAAAAAAAAAAAACf\/EABQQAQAAAAAAAAAAAAAAAAAAAAD\/2gAIAQEAAD8AKp\/\/2Q==",
                    "detail": "low"
                }
            },
            {
                "type": "image_url",
                "image_url": {
                    "url": "data:image\/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8\/5+hHgAHggJ\/PchI7wAAAABJRU5ErkJggg==",
                    "detail": "low"
                }
            },
            {
                "type": "image_url",
                "image_url": {
                    "url": "data:image\/gif;base64,R0lGODlhAQABAIAAAAAAAP\/\/\/wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==",
                    "detail": "low"
                }
            }
        ]
    }
    JSON;

    $images = [
        new ImageSource('https://example.test/image.jpg'),
        new ImageSource('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==', ImageQuality::Low),
        new ImageSource('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', ImageQuality::Low),
        new ImageSource('R0lGODlhAQABAIAAAAAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', ImageQuality::Low),
    ];

    expect(\json_encode(VisionMessage::fromImages($images, 'What are in these images? Is there any difference between them?'), JSON_PRETTY_PRINT))->toBe($expectedJson);
});

it('does not accept wrong contents', function () {
    $images = [
        new ImageSource('This is not a valid image'),
    ];

    VisionMessage::fromImages($images);
})->throws(\InvalidArgumentException::class);
