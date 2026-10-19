<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\GeminiOpenAIConfig;
use LLPhant\OpenAIConfig;
use Mockery;
use OpenAI;
use OpenAI\Contracts\TransporterContract;
use OpenAI\ValueObjects\Transporter\Response as TransporterResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\AbstractLogger;

it('no error when construct with no model', function () {
    $config = new OpenAIConfig();
    $config->apiKey = 'fakeapikey';
    $chat = new OpenAIChat($config);
    expect(isset($chat))->toBeTrue();
});

it('can process system, user, assistant and functionResult messages', function () {
    $client = new MockOpenAIClient();

    $config = new OpenAIConfig();
    $config->client = $client;

    $logger = new class extends AbstractLogger
    {
        public array $logs = [];

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }
    };

    $chat = new OpenAIChat($config, $logger);

    $messages = [
        Message::system('You are an AI that answers to questions about weather in certain locations by calling external services to get the information'),
        Message::user('What is the weather in Venice?'),
        Message::functionResult(
            'Weather in Venice is sunny, temperature is 26 Celsius',
            'currentWeatherForLocation'
        ),
    ];
    $response = $chat->generateChatOrReturnFunctionToCall($messages);

    expect($response)->toBeString();
    expect($logger->logs)->toHaveCount(2);
    expect(array_map(fn ($l) => $l['message'], $logger->logs))->toBe([
        'Calling Chat::create',
        'Received Chat::create answer',
    ]);
    expect(array_map(fn ($l) => $l['level'], $logger->logs))->toBe(['debug', 'debug']);
});

it('returns a stream response using generateStreamOfText()', function () {
    $response = new Response(
        200,
        [],
        'This is the response from OpenAI'
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows([
        'requestStream' => $response,
    ]);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateStreamOfText('this is the prompt question');
    expect($response)->toBeInstanceof(StreamInterface::class);
});

it('returns a stream response using generateChatStream()', function () {
    $response = new Response(
        200,
        [],
        'This is the response from OpenAI'
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows([
        'requestStream' => $response,
    ]);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateChatStream([Message::user('here the question')]);
    expect($response)->toBeInstanceof(StreamInterface::class);
});

it('returns last response using generateText()', function () {
    $response = TransporterResponse::from(
        fixture('OpenAI/chat-response'),
        ['x-request-id' => '1']
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows()->requestObject(anyArgs())->andReturns($response);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateText('here the question');
    $lastResponse = $chat->getLastResponse();

    expect($lastResponse->id)->toBe('chatcmpl-123');
    expect($lastResponse->object)->toBe('chat.completion');
    expect($lastResponse->model)->toBe('gpt-3.5-turbo-0125');
    expect($lastResponse->usage->promptTokens)->toBe(9);
    expect($lastResponse->usage->completionTokens)->toBe(12);
    expect($lastResponse->usage->totalTokens)->toBe(21);
});

it('returns last response using generateTextOrReturnFunctionToCall()', function () {
    $response = TransporterResponse::from(
        fixture('OpenAI/chat-response'),
        ['x-request-id' => '1']
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows()->requestObject(anyArgs())->andReturns($response);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateText('here the question');
    $lastResponse = $chat->getLastResponse();
    expect($lastResponse->usage->promptTokens)->toBe(9);
    expect($lastResponse->usage->completionTokens)->toBe(12);
    expect($lastResponse->usage->totalTokens)->toBe(21);
});

it('returns empty (null) last response if no usage', function () {
    $transport = Mockery::mock(TransporterContract::class);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    expect($chat->getLastResponse())->toBe(null);
});

it('returns total token usage generate() or generateTextOrReturnFunctionToCall()', function () {
    $response = TransporterResponse::from(
        fixture('OpenAI/chat-response'),
        ['x-request-id' => '1']
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows()->requestObject(anyArgs())->andReturns($response);

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateText('here the question');
    expect($chat->getTotalTokens())->toBe(21);

    $response = $chat->generateTextOrReturnFunctionToCall('here the second question with function');
    expect($chat->getTotalTokens())->toBe(42);
});

it('can be supplied with a custom client', function () {
    $client = new MockOpenAIClient();

    $config = new OpenAIConfig();
    $config->client = $client;

    $chat = new OpenAIChat($config);
    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one ?');
    expect($response)->toBeString()
        ->and($response)->toBe("\n\nHello there, this is a fake chat response.");
    // See OpenAI\Testing\Responses\Fixtures\Chat\CreateResponseFixture
});

it('does not throw away "0" strings when creating streamed response', function () {
    $data = [
        'id' => 'test',
        'object' => 'test',
        'created' => 0,
        'model' => 'test',
        'choices' => [
            [
                'index' => 0,
                'delta' => [
                    'content' => '0',
                ],
            ],
        ],
    ];

    $encodedDataAsChars = str_split('data:'.json_encode($data));

    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('eof')->andReturnUsing(function () use (&$encodedDataAsChars) {
        return empty($encodedDataAsChars);
    });
    $stream->shouldReceive('read')->andReturnUsing(function () use (&$encodedDataAsChars) {
        return array_shift($encodedDataAsChars) ?? "\n";
    });

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows([
        'getBody' => $stream,
    ]);
    $response->shouldReceive('getHeaders')->andReturns([]);

    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows([
        'requestStream' => $response,
    ]);
    $transport->shouldReceive('requestObject');

    $config = new OpenAIConfig();
    $config->client = new \OpenAI\Client($transport);
    $chat = new OpenAIChat($config);

    $response = $chat->generateChatStream([Message::user('here the question')]);
    expect($response)->toBeInstanceof(StreamInterface::class);
    expect($response->read(100))->toBe('0');
});

it('OpenAIChat generateChat loops for MULTIPLE rounds of tool calls', function () {
    $openAIAnswer1 = <<<'JSON'
    {
      "id": "chatcmpl-1",
      "object": "chat.completion",
      "created": 1677652288,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": null,
          "tool_calls": [{ "id": "call_1", "type": "function", "function": { "name": "tool1", "arguments": "{}" } }]
        },
        "finish_reason": "tool_calls"
      }]
    }
    JSON;

    $openAIAnswer2 = <<<'JSON'
    {
      "id": "chatcmpl-2",
      "object": "chat.completion",
      "created": 1677652289,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": null,
          "tool_calls": [{ "id": "call_2", "type": "function", "function": { "name": "tool2", "arguments": "{}" } }]
        },
        "finish_reason": "tool_calls"
      }]
    }
    JSON;

    $openAIAnswer3 = <<<'JSON'
    {
      "id": "chatcmpl-3",
      "object": "chat.completion",
      "created": 1677652290,
      "model": "gpt-3.5-turbo",
      "choices": [{
        "index": 0,
        "message": {
          "role": "assistant",
          "content": "Final Answer"
        },
        "finish_reason": "stop"
      }]
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer1),
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer2),
        new Response(200, ['Content-Type' => 'application/json'], $openAIAnswer3),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new GuzzleClient(['handler' => $handlerStack]);

    $openAIClient = OpenAI::factory()
        ->withApiKey('fake-key')
        ->withHttpClient($httpClient)
        ->make();

    $config = new OpenAIConfig();
    $config->model = 'gpt-3.5-turbo';
    $config->client = $openAIClient;
    $chat = new OpenAIChat($config);

    $obj = new class
    {
        public int $calls = 0;

        public function tool1()
        {
            $this->calls++;

            return 'res1';
        }

        public function tool2()
        {
            $this->calls++;

            return 'res2';
        }
    };

    $chat->addTool(new FunctionInfo('tool1', $obj, 'desc', []));
    $chat->addTool(new FunctionInfo('tool2', $obj, 'desc', []));

    $response = $chat->generateChat([Message::user('trigger tools')]);

    expect($response)->toBe('Final Answer');
    expect($obj->calls)->toBe(2);
});

it('passes the arguments streamed across several tool call deltas to the function', function () {
    $chunk = fn (array $delta, ?string $finishReason = null): string => 'data: '.json_encode([
        'id' => 'chatcmpl-stream',
        'object' => 'chat.completion.chunk',
        'created' => 1677652288,
        'model' => 'gpt-4o-mini',
        'choices' => [['index' => 0, 'delta' => $delta, 'finish_reason' => $finishReason]],
    ])."\n\n";

    $stream = $chunk(['role' => 'assistant', 'content' => null])
        .$chunk(['tool_calls' => [['index' => 0, 'id' => 'call_paris', 'type' => 'function', 'function' => ['name' => 'getWeather', 'arguments' => '']]]])
        .$chunk(['tool_calls' => [['index' => 0, 'function' => ['arguments' => '{"city":']]]])
        .$chunk(['tool_calls' => [['index' => 0, 'function' => ['arguments' => ' "Paris"}']]]])
        .$chunk(['tool_calls' => [['index' => 1, 'id' => 'call_london', 'type' => 'function', 'function' => ['name' => 'getWeather', 'arguments' => '']]]])
        .$chunk(['tool_calls' => [['index' => 1, 'function' => ['arguments' => '{"city": "London"}']]]])
        .$chunk([], 'tool_calls')
        ."data: [DONE]\n\n";

    $finalAnswer = <<<'JSON'
    {
      "id": "chatcmpl-final",
      "object": "chat.completion",
      "created": 1677652289,
      "model": "gpt-4o-mini",
      "choices": [{
        "index": 0,
        "message": { "role": "assistant", "content": "Cloudy in both cities." },
        "finish_reason": "stop"
      }]
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'text/event-stream'], $stream),
        new Response(200, ['Content-Type' => 'application/json'], $finalAnswer),
    ]);
    $openAIClient = OpenAI::factory()
        ->withApiKey('fake-key')
        ->withHttpClient(new GuzzleClient(['handler' => HandlerStack::create($mock)]))
        ->make();

    $config = new OpenAIConfig();
    $config->model = 'gpt-4o-mini';
    $config->client = $openAIClient;
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

    expect($response->getContents())->toBe('Cloudy in both cities.');
    expect($obj->cities)->toBe(['Paris', 'London']);
});

it('calls every streamed tool call when the stream has no indexes and finishes with stop', function () {
    // Gemini's OpenAI-compatible endpoint sends each parallel call whole in its own delta,
    // without an index, and ends the turn with finish_reason "stop"
    $stream = file_get_contents(__DIR__.'/../../Fixtures/OpenAI/gemini-openai-compat-parallel-tool-calls.sse');

    $finalAnswer = <<<'JSON'
    {
      "id": "chatcmpl-final",
      "object": "chat.completion",
      "created": 1792316691,
      "model": "gemini-3.5-flash",
      "choices": [{
        "index": 0,
        "message": { "role": "assistant", "content": "Cloudy in both cities." },
        "finish_reason": "stop"
      }]
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'text/event-stream'], $stream),
        new Response(200, ['Content-Type' => 'application/json'], $finalAnswer),
    ]);
    $openAIClient = OpenAI::factory()
        ->withApiKey('fake-key')
        ->withHttpClient(new GuzzleClient(['handler' => HandlerStack::create($mock)]))
        ->make();

    $config = new OpenAIConfig();
    $config->model = 'gemini-3.5-flash';
    $config->client = $openAIClient;
    $chat = new OpenAIChat($config);

    $obj = new class
    {
        /** @var string[] */
        public array $cities = [];

        public function get_weather(string $city): string
        {
            $this->cities[] = $city;

            return "{$city}: 18 C, cloudy";
        }
    };

    $chat->addTool(new FunctionInfo('get_weather', $obj, 'Gets the weather', [new Parameter('city', 'string', 'The city')], [new Parameter('city', 'string', 'The city')]));

    $response = $chat->generateChatStream([Message::user('Weather in Paris and Tokyo?')]);

    expect($response->getContents())->toBe('Cloudy in both cities.');
    expect($obj->cities)->toBe(['Paris', 'Tokyo']);
});

it('defaults to a model OpenAI still serves', function () {
    $config = new OpenAIConfig(apiKey: 'fakeapikey');

    // gpt-4-turbo shuts down on 2026-10-23, see https://developers.openai.com/api/docs/deprecations
    expect($config->model)->toBe('gpt-5.6-sol');
});

it('sets reasoning_effort to none when GPT-5 tools are sent through chat completions', function () {
    $config = new OpenAIConfig();
    $config->client = new MockOpenAIClient();
    $chat = new OpenAIChat($config);

    $tool = new class
    {
        public function ping(): string
        {
            return 'pong';
        }
    };

    $chat->addTool(new FunctionInfo('ping', $tool, 'Simple ping', []));
    $chat->generateChatOrReturnFunctionToCall([Message::user('call ping')]);

    expect($chat->argsLog[0]['reasoning_effort'])->toBe('none');
});

it('keeps call association in Gemini follow-up transcript for parallel tool calls', function () {
    $stream = file_get_contents(__DIR__.'/../../Fixtures/OpenAI/gemini-openai-compat-parallel-tool-calls.sse');

    $finalAnswer = <<<'JSON'
    {
      "id": "chatcmpl-final",
      "object": "chat.completion",
      "created": 1792316691,
      "model": "gemini-3.5-flash",
      "choices": [{
        "index": 0,
        "message": { "role": "assistant", "content": "Cloudy in both cities." },
        "finish_reason": "stop"
      }]
    }
    JSON;

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'text/event-stream'], $stream),
        new Response(200, ['Content-Type' => 'application/json'], $finalAnswer),
    ]);
    $openAIClient = OpenAI::factory()
        ->withApiKey('fake-key')
        ->withHttpClient(new GuzzleClient(['handler' => HandlerStack::create($mock)]))
        ->make();

    $config = new GeminiOpenAIConfig('fake-key');
    $config->model = 'gemini-3.5-flash';
    $config->client = $openAIClient;
    $chat = new OpenAIChat($config);

    $obj = new class
    {
        public function get_weather(string $city): string
        {
            return '18 C';
        }
    };

    $chat->addTool(new FunctionInfo('get_weather', $obj, 'Gets the weather', [new Parameter('city', 'string', 'The city')], [new Parameter('city', 'string', 'The city')]));

    $response = $chat->generateChatStream([Message::user('Weather in Paris and Tokyo?')]);

    expect($response->getContents())->toBe('Cloudy in both cities.');

    $followUpMessages = $chat->argsLog[1]['messages'];
    $assistantMessages = array_filter(
        $followUpMessages,
        static fn (Message $message): bool => $message->role->value === 'assistant'
    );
    expect($assistantMessages)->toBe([]);

    $functionMessages = array_values(array_filter(
        $followUpMessages,
        static fn (Message $message): bool => $message->role->value === 'function'
    ));
    expect($functionMessages)->toHaveCount(2);

    $payloadByCallId = [];
    foreach ($functionMessages as $functionMessage) {
        /** @var array{tool_call_id: string, arguments: array{city: string}, result: string} $payload */
        $payload = json_decode($functionMessage->content, true, 512, JSON_THROW_ON_ERROR);
        $payloadByCallId[$payload['tool_call_id']] = $payload;
        expect($functionMessage->name)->toBe('get_weather');
        expect($payload['result'])->toBe('18 C');
    }

    expect($payloadByCallId)->toHaveKey('call_4234484');
    expect($payloadByCallId)->toHaveKey('call_4234487');
    expect($payloadByCallId['call_4234484']['arguments'])->toBe(['city' => 'Paris']);
    expect($payloadByCallId['call_4234487']['arguments'])->toBe(['city' => 'Tokyo']);
});
