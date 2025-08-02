<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Chat\Vision\ImageSource;
use LLPhant\Chat\Vision\VisionMessage;
use LLPhant\OpenAIConfig;

it('can describe images with urls', function () {
    $config = new OpenAIConfig();
    $config->model = 'gpt-4o-mini';
    $chat = new OpenAIChat($config);
    $messages = [
        VisionMessage::fromImages([
            new ImageSource('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/Lecco_riflesso.jpg/800px-Lecco_riflesso.jpg'),
            new ImageSource('https://upload.wikimedia.org/wikipedia/commons/thumb/9/9c/Lecco_con_riflessi_all%27alba.jpg/640px-Lecco_con_riflessi_all%27alba.jpg'),
        ], 'What is represented in these images?'),
    ];
    $response = $chat->generateChat($messages);
    expect($response)->toContain('lake', 'mountain');
});

it('can describe images in base64', function (string $fileName) {
    $config = new OpenAIConfig();
    $config->model = 'gpt-4o-mini';
    $chat = new OpenAIChat($config);
    $fileContents = \file_get_contents(__DIR__.'/'.$fileName);
    $base64 = \base64_encode($fileContents);
    $messages = [
        VisionMessage::fromImages([
            new ImageSource($base64),
        ]),
    ];
    $response = $chat->generateChat($messages);
    expect($response)->toContain('cat');
})->with([
    'test.jpg',
    'test.png',
    'test.gif',
    'test.webp',
]);
