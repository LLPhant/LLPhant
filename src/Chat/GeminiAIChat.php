<?php

namespace LLPhant\Chat;

use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\CalledFunction\CalledFunction;
use LLPhant\GeminiOpenAIConfig;
use OpenAI\Responses\Chat\CreateStreamedResponse;
use OpenAI\Responses\StreamResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GeminiAIChat extends OpenAIChat
{
    public function __construct(GeminiOpenAIConfig $config = new GeminiOpenAIConfig(), LoggerInterface $logger = new NullLogger())
    {
        parent::__construct($config, $logger);
    }

    /**
     * Gemini ends streamed tool-call turns with finish_reason "stop".
     */
    protected function shouldCallStreamedTools(?string $finishReason): bool
    {
        return $finishReason !== null && $finishReason !== 'length' && $finishReason !== 'content_filter';
    }

    protected function shouldAddAssistantToolCallsMessage(): bool
    {
        return false;
    }

    protected function createMessageWithToolOutput(CalledFunction $functionCalled): Message
    {
        return Message::functionResult($this->createStructuredFunctionResultPayload($functionCalled), $functionCalled->definition->name);
    }

    /**
     * @param  Message[]  $messages
     */
    protected function createStreamedResponse(array $messages): StreamInterface
    {
        $openAiArgs = $this->getOpenAiArgs($messages);

        $this->logger->debug('Calling Chat::createStreamed', [
            'chat' => self::class,
            'args' => $openAiArgs,
        ]);

        $stream = $this->client->chat()->createStreamed($openAiArgs);
        $response = $this->extractRawStreamResponse($stream);

        $generator = function (ResponseInterface $response) use ($messages) {
            $toolsToCall = [];

            while (! $response->getBody()->eof()) {
                $line = $this->readLineFromStream($response->getBody());

                $event = null;
                if (str_starts_with($line, 'event:')) {
                    $event = trim(substr($line, strlen('event:')));
                    $line = $this->readLineFromStream($response->getBody());
                }

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, strlen('data:')));
                if ($data === '[DONE]') {
                    break;
                }

                $chunk = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                if ($event !== null) {
                    $chunk['__event'] = $event;
                }

                if (isset($chunk['error'])) {
                    throw new \RuntimeException('Gemini stream error: '.json_encode($chunk['error'], JSON_THROW_ON_ERROR));
                }

                $choices = $chunk['choices'] ?? [];
                if (! is_array($choices) || $choices === []) {
                    continue;
                }

                $choice = $choices[0];
                $delta = $choice['delta'] ?? [];
                $toolCalls = $delta['tool_calls'] ?? [];
                foreach ($toolCalls as $toolCall) {
                    if (! is_array($toolCall) || ! isset($toolCall['function']) || ! is_array($toolCall['function'])) {
                        continue;
                    }

                    $functionName = $toolCall['function']['name'] ?? null;
                    $argumentsDelta = (string) ($toolCall['function']['arguments'] ?? '');
                    $indexFromPayload = isset($toolCall['index']) && is_int($toolCall['index']) ? $toolCall['index'] : null;
                    $callId = isset($toolCall['id']) && is_string($toolCall['id']) ? $toolCall['id'] : null;
                    $extraContent = isset($toolCall['extra_content']) && is_array($toolCall['extra_content']) ? $toolCall['extra_content'] : null;

                    if (is_string($functionName)) {
                        $toolsToCall[$indexFromPayload ?? count($toolsToCall)] = [
                            'function' => $functionName,
                            'arguments' => $argumentsDelta,
                            'id' => $callId,
                            'extra_content' => $extraContent,
                        ];

                        continue;
                    }

                    $index = $indexFromPayload ?? array_key_last($toolsToCall);
                    if ($index === null || ! isset($toolsToCall[$index])) {
                        continue;
                    }

                    $toolsToCall[$index]['arguments'] .= $argumentsDelta;
                    if (($toolsToCall[$index]['extra_content'] ?? null) === null && $extraContent !== null) {
                        $toolsToCall[$index]['extra_content'] = $extraContent;
                    }
                }

                $finishReason = $choice['finish_reason'] ?? null;
                if ($this->shouldCallStreamedTools(is_string($finishReason) ? $finishReason : null) && $toolsToCall !== []) {
                    foreach ($toolsToCall as $tool) {
                        if (is_string($tool['id'])) {
                            $this->callFunction($tool['function'], $tool['arguments'], $tool['id'], $tool['extra_content'] ?? null);
                        }
                    }
                    $newMessages = $this->getNewMessagesFromTools($messages);
                    if ($newMessages === []) {
                        break;
                    }
                    yield $this->generateChat($newMessages);
                }

                if (! is_null($finishReason)) {
                    break;
                }

                $content = $delta['content'] ?? null;
                if (! is_string($content) || $content === '') {
                    continue;
                }

                yield $content;
            }
        };

        return Utils::streamFor($generator($response));
    }

    /**
     * @return array{
     *     tool_call_id: ?string,
     *     arguments: array<string, mixed>,
     *     result: string,
     *     extra_content?: array<string, mixed>
     * }
     */
    private function createStructuredFunctionResultPayload(CalledFunction $functionCalled): array
    {
        $payload = [
            'tool_call_id' => $functionCalled->tool_call_id,
            'arguments' => $functionCalled->arguments,
            'result' => $functionCalled->return ?? '',
        ];

        if ($functionCalled->extra_content !== null) {
            $payload['extra_content'] = $functionCalled->extra_content;
        }

        return $payload;
    }

    /**
     * @param  StreamResponse<CreateStreamedResponse>  $stream
     *
     * @throws \ReflectionException
     */
    private function extractRawStreamResponse(StreamResponse $stream): ResponseInterface
    {
        $property = new \ReflectionProperty($stream, 'response');

        /** @var ResponseInterface $response */
        $response = $property->getValue($stream);

        return $response;
    }

    private function readLineFromStream(StreamInterface $stream): string
    {
        $buffer = '';

        while (! $stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }
}
