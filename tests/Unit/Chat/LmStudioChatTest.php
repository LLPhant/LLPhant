<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\LmStudioChat;
use LLPhant\Chat\Message;
use LLPhant\LmStudioConfig;
use Tests\Integration\Chat\WeatherExample;

/**
 * @param  list<array<string, mixed>>  $chunks  the JSON payloads of the "data:" lines
 */
function lmStudioSse(array $chunks): string
{
    $lines = array_map(fn (array $chunk): string => 'data: '.json_encode($chunk, JSON_THROW_ON_ERROR)."\n\n", $chunks);

    return implode('', $lines)."data: [DONE]\n\n";
}

/**
 * @param  Response[]  $responses
 * @param  array<int, array<string, mixed>>  $history
 */
function lmStudioChatWithWeatherTool(array $responses, array &$history, WeatherExample $weather): LmStudioChat
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    $chat = new LmStudioChat(new LmStudioConfig(model: 'test'), client: new Client(['handler' => $stack]));
    $chat->addFunction(new FunctionInfo(
        'currentWeatherForLocation',
        $weather,
        'returns the current weather in the given location',
        [new Parameter('location', 'string', 'the name of the city')]
    ));

    return $chat;
}

it('yields the text of a streamed answer', function () {
    $history = [];
    $sse = lmStudioSse([
        ['choices' => [['index' => 0, 'delta' => ['role' => 'assistant', 'content' => 'Hel'], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => ['content' => 'lo'], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop']]],
    ]);
    $chat = lmStudioChatWithWeatherTool([new Response(200, [], $sse)], $history, new WeatherExample());

    expect($chat->generateStreamOfText('hi')->getContents())->toBe('Hello');
});

it('calls the tools of a streamed answer and streams the answer that follows', function (array $chunks) {
    $history = [];
    $weather = new WeatherExample();
    $chat = lmStudioChatWithWeatherTool([
        new Response(200, [], lmStudioSse($chunks)),
        new Response(200, [], json_encode(['choices' => [['message' => ['content' => 'Wear a scarf.']]]], JSON_THROW_ON_ERROR)),
    ], $history, $weather);

    $answer = $chat->generateChatStream([Message::user('Should I wear a scarf in Venice?')])->getContents();

    expect($weather->lastMessage)->toBe('Weather in Venice is sunny, temperature is 26 Celsius')
        ->and($answer)->toBe('Wear a scarf.')
        ->and($history)->toHaveCount(2);

    $followUp = json_decode((string) $history[1]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
    $roles = array_column($followUp['messages'], 'role');
    expect($roles)->toBe(['user', 'assistant', 'tool'])
        ->and($followUp['messages'][1]['tool_calls'][0]['function']['name'])->toBe('currentWeatherForLocation')
        ->and($followUp['messages'][2]['tool_call_id'])->toBe('call_1');
})->with([
    'arguments in fragments, tool_calls finish reason' => [[
        ['choices' => [['index' => 0, 'delta' => ['role' => 'assistant', 'tool_calls' => [
            ['index' => 0, 'id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'currentWeatherForLocation', 'arguments' => '']],
        ]], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => ['tool_calls' => [['index' => 0, 'function' => ['arguments' => '{"location":']]]], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => ['tool_calls' => [['index' => 0, 'function' => ['arguments' => '"Venice"}']]]], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'tool_calls']]],
    ]],
    'whole call in one delta without index, stop finish reason' => [[
        ['choices' => [['index' => 0, 'delta' => ['role' => 'assistant', 'tool_calls' => [
            ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'currentWeatherForLocation', 'arguments' => '{"location":"Venice"}']],
        ]], 'finish_reason' => null]]],
        ['choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop']]],
    ]],
]);
