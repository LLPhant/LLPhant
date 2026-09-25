<?php

namespace Tests\Unit\Chat;

use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\Message;
use LLPhant\Chat\MistralAIChat;
use LLPhant\MistralAIConfig;
use Mockery;
use OpenAI\Client;
use OpenAI\Contracts\TransporterContract;
use Psr\Http\Message\StreamInterface;
use Psr\Log\AbstractLogger;

it('no error when construct with no model', function () {
    $config = new MistralAIConfig();
    $config->apiKey = 'fakeapikey';
    $chat = new MistralAIChat($config);
    expect(isset($chat))->toBeTrue();
});

it('returns a stream response using generateStreamOfText()', function () {
    $logger = new class extends AbstractLogger
    {
        public array $logs = [];

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }
    };

    $response = new Response(
        200,
        [],
        'This is the response from Mistral AI'
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows([
        'requestStream' => $response,
    ]);

    $config = new MistralAIConfig();
    $config->client = new Client($transport);
    $chat = new MistralAIChat($config, $logger);

    $response = $chat->generateStreamOfText('this is the prompt question');
    expect($response)->toBeInstanceof(StreamInterface::class);
    expect($logger->logs)->toHaveCount(1);
    expect(array_map(fn ($l) => $l['message'], $logger->logs))->toBe(['Calling Chat::createStreamed']);
    expect(array_map(fn ($l) => $l['level'], $logger->logs))->toBe(['debug']);
});

it('returns a stream response using generateChatStream()', function () {
    $response = new Response(
        200,
        [],
        'This is the response from Mistral AI'
    );
    $transport = Mockery::mock(TransporterContract::class);
    $transport->allows([
        'requestStream' => $response,
    ]);

    $config = new MistralAIConfig();
    $config->client = new Client($transport);
    $chat = new MistralAIChat($config);

    $response = $chat->generateChatStream([Message::user('here the question')]);
    expect($response)->toBeInstanceof(StreamInterface::class);
});
