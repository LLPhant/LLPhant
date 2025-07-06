<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\Anthropic\AnthropicImage;
use LLPhant\Chat\Anthropic\AnthropicImageType;
use LLPhant\Chat\Anthropic\AnthropicVisionMessage;
use LLPhant\Chat\AnthropicChat;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;

it('can generate some stuff', function () {
    $chat = new AnthropicChat();
    $response = $chat->generateText('what is one + one? Please answer with a word');
    expect($response)->toBeString()->and(strtolower($response))->toContain('two')
        ->and($chat->getTotalTokens())->tobeGreaterThan(0);
});

it('can generate some stuff with a system prompt', function () {
    $chat = new AnthropicChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one?');
    expect(strtolower($response))->toStartWith('ok');
});

it('can generate some stuff using a stream', function () {
    $chat = new AnthropicChat();
    $response = $chat->generateStreamOfText('Can you describe the recipe for making carbonara in 5 steps');
    expect($response->__toString())->toContain('egg')
        ->and($chat->getTotalTokens())->toBeGreaterThan(0);
});

it('can call a function', function () {
    $chat = new AnthropicChat();

    $subject = new Parameter('subject', 'string', 'the subject of the mail');
    $body = new Parameter('body', 'string', 'the body of the mail');
    $email = new Parameter('email', 'string', 'the email address');

    $mockMailerExample = new MailerExample();

    $function = new FunctionInfo(
        'sendMail',
        $mockMailerExample,
        'send a mail',
        [$subject, $body, $email]
    );

    $chat->addFunction($function);
    $chat->setSystemMessage('You are an AI that deliver information using the email system. When you have enough information to answer the question of the user you send a mail');
    $chat->generateText('Who is Marie Curie in one line? My email is student@foo.com');

    expect($mockMailerExample->lastMessage)->toStartWith('The email has been sent to student@foo.com with the subject ')
        ->and($chat->lastFunctionCalled)->toBe($function)
        ->and($chat->getTotalTokens())->toBeGreaterThan(0);
});

it('can use the result of a function', function () {
    $chat = new AnthropicChat();

    $location = new Parameter('location', 'string', 'the location i.e. the name of the city, the state or province and the nation');

    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addFunction($function);
    $chat->setSystemMessage('You are an AI that answers to questions about weather in certain locations by calling external services to get the information');
    $answer = $chat->generateText('What is the weather in Venice?');

    expect($weatherExample->lastMessage)->toContain('Venice')
        ->and($chat->lastFunctionCalled)->toBe($function)
        ->and($answer)->toContain('Venice')
        ->and($answer)->toContain('sunny')
        ->and($answer)->toContain('26');
});

it('can describe images in base64', function () {
    $chat = new AnthropicChat();
    $fileContents = \file_get_contents(__DIR__.'/Vision/test.jpg');
    $base64 = \base64_encode($fileContents);
    $messages = [
        new AnthropicVisionMessage([new AnthropicImage(AnthropicImageType::JPEG, $base64)]),
    ];
    $response = $chat->generateChat($messages);
    expect($response)->toContain('cat');
});

it('can use message for asking something on images', function () {
    $chat = new AnthropicChat();
    $fileContents = \file_get_contents(__DIR__.'/Vision/test.jpg');
    $base64 = \base64_encode($fileContents);
    $messages = [
        new AnthropicVisionMessage([new AnthropicImage(AnthropicImageType::JPEG, $base64)], 'How many cats are there in this image? Answer in words'),
    ];
    $response = $chat->generateChat($messages);
    expect($response)->toContain('one');
});
