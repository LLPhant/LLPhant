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
                    "url": "data:image\/jpeg;base64,\/9j\/4AAQSkZJRgABAQAAAQABAAD\/7QBwUGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAFMcAVoAAxslRxwCAAACAAAcAnQAP8KpIFZpY0ZyZWVkb21pbmQgLSBodHRwOi8vd3d3LnJlZGJ1YmJsZS5jb20vcGVvcGxlL3ZpY2ZyZWVkb21pbgD\/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT\/wgALCAPoAu4BASIA\/8QAHQABAAICAwEBAAAAAAAAAAAAAAcIBQYDBAkCAf\/aAAgBAQAAAAG0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1+qmpAAAAAAAAAAAAAAB9SfaChmFmEAAAAAAAAAAAAAAOOAbD1kvNNgAAAADq6tuY4Y7irS5eg3RAHZnSy\/eAAAAACn9ceG9szgAAAAIv37AVw2SWtk62pdiguzdyPwCylos8AAAAAKiVmXtmcAAAADWO3VXdZ\/hLtVT\/exYqoXWABIPoP3AAAAAColZl7ZnAAAAA1vV5LormrHxhAkbu70gAE23nAAAAAKiVmXtmcAAAAEORPO\/HxUL\/ZTifoAAB9em+bAAAAAVErMvbM4AAAAcXTyMJVUx\/",
                    "detail": "low"
                }
            }
        ]
    }
    JSON;

    $images = [
        new ImageSource('https://example.test/image.jpg'),
        new ImageSource('/9j/4AAQSkZJRgABAQAAAQABAAD/7QBwUGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAFMcAVoAAxslRxwCAAACAAAcAnQAP8KpIFZpY0ZyZWVkb21pbmQgLSBodHRwOi8vd3d3LnJlZGJ1YmJsZS5jb20vcGVvcGxlL3ZpY2ZyZWVkb21pbgD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/wgALCAPoAu4BASIA/8QAHQABAAICAwEBAAAAAAAAAAAAAAcIBQYDBAkCAf/aAAgBAQAAAAG0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1+qmpAAAAAAAAAAAAAAB9SfaChmFmEAAAAAAAAAAAAAAOOAbD1kvNNgAAAADq6tuY4Y7irS5eg3RAHZnSy/eAAAAACn9ceG9szgAAAAIv37AVw2SWtk62pdiguzdyPwCylos8AAAAAKiVmXtmcAAAADWO3VXdZ/hLtVT/exYqoXWABIPoP3AAAAAColZl7ZnAAAAA1vV5LormrHxhAkbu70gAE23nAAAAAKiVmXtmcAAAAEORPO/HxUL/ZTifoAAB9em+bAAAAAVErMvbM4AAAAcXTyMJVUx/', ImageQuality::Low),
    ];

    expect(\json_encode(VisionMessage::fromImages($images, 'What are in these images? Is there any difference between them?'), JSON_PRETTY_PRINT))->toBe($expectedJson);
});

it('does not accept wrong contents', function () {
    $images = [
        new ImageSource('This is not a valid image'),
    ];

    VisionMessage::fromImages($images);
})->throws(\InvalidArgumentException::class);
