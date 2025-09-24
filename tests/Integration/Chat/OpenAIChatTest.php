<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\Enums\OpenAIChatModel;
use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Exception\HttpException;
use LLPhant\OpenAIConfig;
use Mockery;

it('can generate some stuff', function () {
    $chat = new OpenAIChat();
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff with a system prompt', function () {
    $chat = new OpenAIChat();
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one ?');
    // Sometimes a final dot is added to the answer
    expect(strtolower($response))->toStartWith('ok');
});

it('can load any existing model', function () {
    $config = new OpenAIConfig();
    $config->model = 'gpt-3.5-turbo-16k';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('one + one ?');
    expect($response)->toBeString();
});

it('can call a function', function () {
    $chat = new OpenAIChat();

    $subject = new Parameter('subject', 'string', 'the subject of the mail');
    $body = new Parameter('body', 'string', 'the body of the mail');
    $email = new Parameter('email', 'string', 'the email address');

    $mockMailerExample = Mockery::mock(MailerExample::class);
    $mockMailerExample->shouldReceive('sendMail')->once()->andReturn('The email has been sent to..');

    $function = new FunctionInfo(
        'sendMail',
        $mockMailerExample,
        'send a mail',
        [$subject, $body, $email]
    );

    $chat->addFunction($function);
    $chat->setSystemMessage('You are an AI that deliver information using the email system. When you have enough information to answer the question of the user you send a mail');
    $chat->generateText('Who is Marie Curie in one line? My email is student@foo.com');
});

it('calls tool functions during a chat', function () {
    $chat = new OpenAIChat();
    $notifier = new NotificationExample();

    $functionSendNotification = FunctionBuilder::buildFunctionInfo($notifier, 'sendNotificationToSlack');

    $chat->addTool($functionSendNotification);
    $messages = [
        Message::system('You need to call the function to send a confirmation notification to slack'),
        Message::user('the confirmation should be called'),
    ];

    $chat->generateChat($messages);

    expect($notifier->nrOfCalls)->toBe(1);
});

it('can call a function and provide the result to the assistant', function () {
    $config = new OpenAIConfig();
    //Functions work only with older models. Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt35Turbo->value;
    $chat = new OpenAIChat($config);
    $location = new Parameter('location', 'string', 'the name of the city, the state or province and the nation');
    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addTool($function);
    $chat->setSystemMessage('You are an AI that answers to questions about weather in certain locations by calling external services to get the information');

    $messages = [
        Message::user('What is the weather in Venice?'),
    ];
    $toolsCalled = $chat->generateChatOrReturnFunctionCalled($messages);

    $firstTool = $toolsCalled[0];

    expect($firstTool->name)->toBe('currentWeatherForLocation');

    $firstRequestTokenUsage = $chat->getTotalTokens();

    $arguments = json_decode($firstTool->jsonArgs, true, 512, JSON_THROW_ON_ERROR);
    $functionResult = $firstTool->instance->{$firstTool->name}(...$arguments);

    $messages[] = Message::functionResult(
        $functionResult,
        $firstTool->name
    );

    $response = $chat->generateChatOrReturnFunctionCalled($messages);

    expect($response)->toBeString()
        ->and($response)->toContain('sunny')
        ->and($chat->getTotalTokens())->toBeGreaterThan($firstRequestTokenUsage);
});

it('can call a tool and provide the result to the assistant', function () {
    $config = new OpenAIConfig();
    //Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt4Turbo->value;
    $chat = new OpenAIChat($config);
    $location = new Parameter('location', 'string', 'the name of the city, the state or province and the nation');
    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addTool($function);
    $chat->setSystemMessage('You are an AI that answers to questions about weather in certain locations by calling external services to get the information');

    $messages = [
        Message::user('What is the weather in Venice?'),
    ];
    $toolsCalled = $chat->generateChatOrReturnFunctionCalled($messages);
    $firstTool = $toolsCalled[0];

    expect($firstTool->name)->toBe('currentWeatherForLocation');

    $firstRequestTokenUsage = $chat->getTotalTokens();

    $toolCallId = $firstTool->getToolCallId();
    expect($toolCallId)->toBeString();

    $messages = array_merge($messages, $firstTool->callAndReturnAsOpenAIMessages());

    $response = $chat->generateChatOrReturnFunctionCalled($messages);

    expect($response)->toBeString()
        ->and($response)->toContain('sunny')
        ->and($chat->getTotalTokens())->toBeGreaterThan($firstRequestTokenUsage);
});

it('can generate a chat stream', function () {
    $config = new OpenAIConfig();
    $config->model = 'gpt-4o-mini';
    $chat = new OpenAIChat($config);

    $messages[] = Message::user('Tell me the names of the first 5 roman emperors');

    $chatStreamOutput = $chat->generateChatStream($messages);

    expect($chatStreamOutput->getContents())->toContain('Caligula');
});

it('can call a function with streaming', function () {
    $config = new OpenAIConfig();
    //Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt35Turbo->value;
    $chat = new OpenAIChat($config);

    $testFunction = new TestFunctionLLPhant();
    $tool = new FunctionInfo('getFavouritePetName', $testFunction, 'This function returns the name of the favourite pet of the user', []);
    $chat->addTool($tool);

    $messages[] = Message::user('What is the name of my favourite pet?');
    $chatOutput = $chat->generateChat($messages);

    expect($chatOutput)->toContain($testFunction->getFavouritePetName());

    $chatStreamOutput = $chat->generateChatStream($messages);

    expect($chatStreamOutput->getContents())->toContain($testFunction->getFavouritePetName());
});

it('reports errors correctly', function () {
    $config = new OpenAIConfig();
    $config->apiKey = 'fake key';
    $chat = new OpenAIChat($config);
    $chat->generateText('Hello!');
})->throws(HttpException::class, '401');
