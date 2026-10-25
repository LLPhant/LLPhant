<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\GeminiAIChat;
use LLPhant\Chat\Message;
use LLPhant\GeminiOpenAIConfig;

it('can generate some stuff', function () {
    $config = new GeminiOpenAIConfig();
    $config->apiKey = getenv('GEMINI_API_KEY');
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new GeminiAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff getting API key from env', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new GeminiAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('passes the arguments streamed across several tool call deltas to the function', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new GeminiAIChat($config);

    $obj = new class
    {
        /** @var string[] */
        public array $cities = [];

        public function getWeather(string $city): string
        {
            $this->cities[] = $city;

            return "{$city}: 18 C, cloudy";
        }
    };

    $chat->addTool(new FunctionInfo('getWeather', $obj, 'Gets the weather', [new Parameter('city', 'string', 'The city')], [new Parameter('city', 'string', 'The city')]));

    $response = $chat->generateChatStream([Message::user('Weather in Paris and London?')]);

    expect(strtolower($response->getContents()))->toContain('cloudy');
    sort($obj->cities);
    expect($obj->cities)->toBe(['London', 'Paris']);
});

it('keeps Gemini thought signatures in function follow-up payloads after streamed tool calls', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-3.5-flash';
    $chat = new GeminiAIChat($config);
    $chat->setSystemMessage('You MUST call get_weather exactly once before answering.');

    $obj = new class
    {
        public function get_weather(string $city): string
        {
            return '18 C';
        }
    };

    $chat->addTool(new FunctionInfo(
        'get_weather',
        $obj,
        'Gets the weather',
        [new Parameter('city', 'string', 'The city')],
        [new Parameter('city', 'string', 'The city')]
    ));

    $response = $chat->generateChatStream([Message::user('What is the weather in Paris?')]);
    expect($response->getContents())->toBeString();
    expect($chat->argsLog)->toHaveCount(2);

    /** @var array<int, Message> $followUpMessages */
    $followUpMessages = $chat->argsLog[1]['messages'];
    $functionMessages = array_values(array_filter(
        $followUpMessages,
        static fn (Message $message): bool => $message->role->value === 'function'
    ));

    expect($functionMessages)->toHaveCount(1);

    /** @var array{extra_content: array{google: array{thought_signature: string}}} $payload */
    $payload = json_decode($functionMessages[0]->content, true, 512, JSON_THROW_ON_ERROR);
    expect($payload['extra_content']['google']['thought_signature'])->toBeString();
});
