<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;

function ollamaChat(): OllamaChat
{
    $config = new OllamaConfig();
    // We need a model that can run tools. See https://ollama.com/blog/tool-support
    // Please note that at the moment (August 2024) llama3.1 model is terrible at using tools, crating a lot of hallucinations
    $config->model = 'mistral-nemo';
    $config->url = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/';

    return new OllamaChat($config);
}

it('can generate some stuff', function () {
    $chat = ollamaChat();
    $response = $chat->generateText('what is 1 + 1?');
    expect($response)->toBeString()->and($response)->toContain('2');
});

it('can generate some stuff with a system prompt', function () {
    $chat = ollamaChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one?');
    expect(strtolower($response))->toContain('ok');
});

it('can generate chat with a system prompt', function () {
    $chat = ollamaChat();

    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $messages = [
        Message::user('what is one + one?'),
    ];

    $response = $chat->generateChat($messages);
    expect(strtolower($response))->toContain('ok');
});

it('can generate some stuff using a stream', function () {
    $chat = ollamaChat();
    $response = $chat->generateStreamOfText('Can you describe the recipe for making carbonara in 5 steps');
    expect($response->__toString())->toContain('eggs');
});

it('can call a function', function () {
    $chat = ollamaChat();

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

    $messages = [
        Message::system('You are an AI that deliver information using the email system. When you have enough information to answer the question of the user you send a mail. YOU MUST NOT USE TOOLS THAT ARE NOT PROVIDED IN THE TOOLS LIST!'),
        Message::user('Who is Marie Curie in one line? My email is student@foo.com'),
    ];

    $chat->generateChat($messages);

    expect($mockMailerExample->lastMessage)->toStartWith('The email has been sent to student@foo.com with the subject ')
        ->and($chat->lastFunctionCalled)->toBe($function)
        ->and($chat->lastToolsOutput)->toStartWith('The email has been sent to');
});

it('can use the result of a function', function () {
    $chat = ollamaChat();

    $location = new Parameter('location', 'string', 'the location i.e. the name of the city, the state or province and the nation');

    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addFunction($function);

    $messages = [
        Message::system('You are an AI that answers to questions about best clothing in a certain area based on the current weather. You use the external system tool currentWeatherForLocation for getting information on the current weather.'),
        Message::user('Should I wear a fur cap and a wool scarf for my trip to Venice?'),
    ];

    $answer = $chat->generateChat($messages);

    expect($weatherExample->lastMessage)->toContain('Venice')
        ->and($chat->lastFunctionCalled)->toBe($function)
        ->and($chat->lastToolsOutput)->toStartWith('Weather in')
        ->and($answer)->toContain('wear');

});
