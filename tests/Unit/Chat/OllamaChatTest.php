<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OllamaChat;
use LLPhant\Exception\MissingParameterException;
use LLPhant\OllamaConfig;
use LLPhant\Utility;
use Psr\Http\Message\StreamInterface;
use Psr\Log\AbstractLogger;
use Tests\Integration\Chat\WeatherExample;

it('not setting any timeout for HTTP client', function () {
    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config);
    expect($chat->client->getConfig('timeout'))->toBeNull();
});

it('is setting a timeout for HTTP client', function () {
    $config = new OllamaConfig();
    $config->model = 'test';
    $config->timeout = 60.0;
    $chat = new OllamaChat($config);
    expect($chat->client->getConfig('timeout'))->toBe(60.0);
});

it('error when construct with no model', function () {
    $config = new OllamaConfig();
    $chat = new OllamaChat($config);
})->throws(MissingParameterException::class, 'You need to specify a model for Ollama');

it('no error when construct with model', function () {
    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config);
    expect(isset($chat))->toBeTrue();
});

it('returns a stream response using generateStreamOfText()', function () {
    $mock = new MockHandler([
        new Response(200, [], 'This is the response from Ollama'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config);
    $chat->client = $client;

    $response = $chat->generateStreamOfText('this is the prompt question');
    expect($response)->toBeInstanceof(StreamInterface::class);
});

it('returns a stream response using generateChatStream()', function () {
    $logger = new class extends AbstractLogger
    {
        public array $logs = [];

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }
    };

    $mock = new MockHandler([
        new Response(200, [], 'This is the response from Ollama'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config, $logger);
    $chat->client = $client;

    $response = $chat->generateChatStream([Message::user('here the question')]);
    expect($response)->toBeInstanceof(StreamInterface::class);
    expect($logger->logs)->toHaveCount(1);
    expect(array_map(fn ($l) => $l['message'], $logger->logs))->toBe(['Calling POST chat']);
    expect(array_map(fn ($l) => $l['level'], $logger->logs))->toBe(['debug']);
});

it('sends correct payload for tools', function () {
    $ollamaAnswer1 = <<<'JSON'
    {
      "model": "mistral-nemo",
      "created_at": "2024-08-10T08:57:06.643581449Z",
      "message": {
        "role": "assistant",
        "content": "",
        "tool_calls": [
          {
            "function": {
              "name": "currentWeatherForLocation",
              "arguments": {
                "location": "Venice"
              }
            }
          }
        ]
      },
      "done_reason": "stop",
      "done": true,
      "total_duration": 12479237987,
      "load_duration": 22603726,
      "prompt_eval_count": 143,
      "prompt_eval_duration": 9529941000,
      "eval_count": 23,
      "eval_duration": 2800636000
    }
    JSON;

    $ollamaAnswer2 = <<<'JSON'
    {
      "model": "mistral-nemo",
      "created_at": "2024-08-10T09:16:48.275273986Z",
      "message": {
        "role": "assistant",
        "content": "In that case, you might want to consider wearing something lighter like a hat and sunglasses. Enjoy your time in Venice!"
      },
      "done_reason": "stop",
      "done": true,
      "total_duration": 5953113541,
      "load_duration": 21340420,
      "prompt_eval_count": 123,
      "prompt_eval_duration": 2541268000,
      "eval_count": 26,
      "eval_duration": 3260077000
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, [], $ollamaAnswer1),
        new Response(200, [], $ollamaAnswer2),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $config = new OllamaConfig();
    $config->model = 'test';
    $chat = new OllamaChat($config);
    $chat->client = $client;

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

    $expectedPayload = <<<'JSON'
    {
      "model": "test",
      "messages": [
        {
          "role": "system",
          "content": "You are an AI that answers to questions about best clothing in a certain area based on the current weather. You use the external system tool currentWeatherForLocation for getting information on the current weather."
        },
        {
          "role": "user",
          "content": "Should I wear a fur cap and a wool scarf for my trip to Venice?"
        },
        {
          "role": "tool",
          "content": "Weather in Venice is sunny, temperature is 26 Celsius"
        }
      ],
      "stream": false,
      "tools": [
        {
          "type": "function",
          "function": {
            "name": "currentWeatherForLocation",
            "description": "returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius",
            "parameters": {
              "type": "object",
              "properties": {
                "location": {
                  "type": "string",
                  "description": "the location i.e. the name of the city, the state or province and the nation"
                }
              },
              "required": []
            }
          }
        }
      ]
    }
    JSON;

    expect(Utility::decodeJson(($mock->getLastRequest()->getBody()->getContents())))->toMatchArray(Utility::decodeJson($expectedPayload))
        ->and($answer)->toBe('In that case, you might want to consider wearing something lighter like a hat and sunglasses. Enjoy your time in Venice!');
});
