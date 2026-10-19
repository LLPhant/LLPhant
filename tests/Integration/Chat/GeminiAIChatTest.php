<?php

declare(strict_types=1);

namespace Tests\Integration\Chat;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\GeminiOpenAIConfig;
use function Tests\Unit\Chat;

it('can generate some stuff', function () {
    $config = new GeminiOpenAIConfig();
    $config->apiKey = getenv('GEMINI_API_KEY');
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('can generate some stuff getting API key from env', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString();
});

it('passes the arguments streamed across several tool call deltas to the function', function () {
    $config = new GeminiOpenAIConfig();
    $config->model = 'gemini-3.5-flash-lite';
    $chat = new OpenAIChat($config);

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
    expect($obj->cities)->toBe(['Paris', 'London']);
});

