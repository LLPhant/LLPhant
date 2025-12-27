<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\LmStudioChat;
use LLPhant\Chat\Message;
use LLPhant\LmStudioConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LmStudioChatTest extends TestCase
{
    public function testGenerateText(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hello! How can I help you today?',
                    ],
                ],
            ],
        ]));

        $httpClient = $this->createMock(Client::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'v1/chat/completions', $this->callback(function ($options) {
                return $options['json']['model'] === 'mister-model' &&
                    $options['json']['messages'][0]['content'] === 'Hello';
            }))
            ->willReturn($mockResponse);

        $config = new LmStudioConfig();
        $config->model = 'mister-model';

        $chat = new LmStudioChat($config);
        $chat->client = $httpClient;

        $response = $chat->generateText('Hello');
        $this->assertEquals('Hello! How can I help you today?', $response);
    }

    public function testGenerateChat(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'The capital of France is Paris.',
                    ],
                ],
            ],
        ]));

        $httpClient = $this->createMock(Client::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $config = new LmStudioConfig();
        $config->model = 'mister-model';

        $chat = new LmStudioChat($config, new NullLogger(), $httpClient);
        $chat->client = $httpClient;

        $messages = [Message::user('What is the capital of France?')];
        $response = $chat->generateChat($messages);

        $this->assertEquals('The capital of France is Paris.', $response);
    }
}
