<?php

namespace LLPhant\Chat;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\CalledFunction\CalledFunction;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\ToolCall;
use LLPhant\Chat\FunctionInfo\ToolFormatter;
use LLPhant\Exception\MissingParameterException;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateResponseToolCall;
use OpenAI\Responses\Chat\CreateStreamedResponseToolCall;
use OpenAI\Responses\StreamResponse;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */ class OpenAIChat implements ChatInterface
{
    private readonly ClientContract $client;

    public string $model;

    /** @var array<string, mixed>[] Permits to easily debug all the messages sent toward OpenAi lib */
    public array $argsLog = [];

    private ?CreateResponse $lastResponse = null;

    private int $totalTokens = 0;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    private Message $systemMessage;

    /** @var FunctionInfo[] */
    private array $tools = [];

    public ?FunctionInfo $lastFunctionCalled = null;

    /** @var CalledFunction[] */
    public array $functionsCalled = [];

    public ?FunctionInfo $requiredFunction = null;

    public function __construct(OpenAIConfig $config = new OpenAIConfig(), private readonly LoggerInterface $logger = new NullLogger())
    {
        if ($config instanceof OpenAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            if (! $config->apiKey) {
                throw new MissingParameterException('You have to provide a OPENAI_API_KEY env var to request OpenAI.');
            }
            if (! $config->url) {
                throw new MissingParameterException('You have to provide an url o to set OPENAI_BASE_URL env var to request OpenAI.');
            }

            $factory = OpenAI::factory()
                ->withApiKey($config->apiKey)
                ->withBaseUri($config->url);

            if ($config->timeout !== null) {
                $options = [
                    'timeout' => $config->timeout,
                    'connect_timeout' => $config->timeout,
                    'read_timeout' => $config->timeout,
                ];
                $factory->withHttpClient(new GuzzleClient($options));
            }

            $this->client = $factory->make();
        }
        $this->model = $config->model ?? throw new MissingParameterException('You have to provide a model');
        $this->modelOptions = $config->modelOptions;
    }

    public function generateText(string $prompt): string
    {
        $answer = $this->generate($prompt);

        $this->handleTools($answer);

        return $this->responseToString($answer);
    }

    public function getLastResponse(): ?CreateResponse
    {
        return $this->lastResponse;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * @return string|FunctionInfo[]
     */
    public function generateTextOrReturnFunctionCalled(string $prompt): string|array
    {
        $this->functionsCalled = [];
        $this->lastFunctionCalled = null;

        $answer = $this->generate($prompt);
        $tools = $this->getToolsToCall($answer);

        if ($tools !== []) {
            return $tools;
        }

        return $this->responseToString($answer);
    }

    public function generateStreamOfText(string $prompt): StreamInterface
    {
        $messages = $this->createOpenAIMessagesFromPrompt($prompt);

        return $this->createStreamedResponse($messages);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChat(array $messages): string
    {
        $answer = $this->generateResponseFromMessages($messages);
        $this->handleTools($answer);

        if ($this->functionsCalled) {
            $newMessages = $this->getNewMessagesFromTools($messages);
            if ($newMessages !== []) {
                $answer = $this->generateResponseFromMessages($newMessages);
            }
        }

        return $this->responseToString($answer);
    }

    /**
     * This function exists to let the developer handle the tools calls on their own.
     * It should not call the tools automatically.
     *
     * @return string|FunctionInfo[]
     *
     * @throws \JsonException
     */
    public function generateChatOrReturnFunctionCalled(array $messages): string|array
    {
        $answer = $this->generateResponseFromMessages($messages);
        $tools = $this->getToolsToCall($answer);

        if ($tools !== []) {
            return $tools;
        }

        return $this->responseToString($answer);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChatStream(array $messages): StreamInterface
    {
        return $this->createStreamedResponse($messages);
    }

    /**
     * We only need one system message in most of the case
     */
    public function setSystemMessage(string $message): void
    {
        $systemMessage = new Message();
        $systemMessage->role = ChatRole::System;
        $systemMessage->content = $message;
        $this->systemMessage = $systemMessage;
    }

    /**
     * @param  FunctionInfo[]  $tools
     */
    public function setTools(array $tools): void
    {
        $this->tools = $tools;
    }

    public function addTool(FunctionInfo $functionInfo): void
    {
        $this->tools[] = $functionInfo;
    }

    /**
     * @deprecated Use setTools instead
     *
     * @param  FunctionInfo[]  $functions
     */
    public function setFunctions(array $functions): void
    {
        $this->tools = $functions;
    }

    /**
     * @deprecated Use addTool instead
     */
    public function addFunction(FunctionInfo $functionInfo): void
    {
        $this->tools[] = $functionInfo;
    }

    public function setModelOption(string $option, mixed $value): void
    {
        $this->modelOptions[$option] = $value;
    }

    private function generate(string $prompt): CreateResponse
    {
        $messages = $this->createOpenAIMessagesFromPrompt($prompt);

        return $this->generateResponseFromMessages($messages);
    }

    /**
     * @return Message[]
     */
    private function createOpenAIMessagesFromPrompt(string $prompt): array
    {
        $userMessage = new Message();
        $userMessage->role = ChatRole::User;
        $userMessage->content = $prompt;

        return [$userMessage];
    }

    /**
     * @param  Message[]  $messages
     */
    private function createStreamedResponse(array $messages): StreamInterface
    {
        $openAiArgs = $this->getOpenAiArgs($messages);

        $this->logger->debug('Calling Chat::createStreamed', [
            'chat' => self::class,
            'args' => $openAiArgs,
        ]);

        $stream = $this->client->chat()->createStreamed($openAiArgs);
        $generator = function (StreamResponse $stream) use ($messages) {
            $toolsToCall = [];
            foreach ($stream as $partialResponse) {
                $toolCalls = $partialResponse->choices[0]->delta->toolCalls ?? [];
                /** @var CreateStreamedResponseToolCall $toolCall */
                foreach ($toolCalls as $toolCall) {
                    if ($toolCall->function->name) {
                        $toolsToCall[] = [
                            'function' => $toolCall->function->name,
                            'arguments' => $toolCall->function->arguments,
                            'id' => $toolCall->id,
                        ];
                    }
                }

                // $functionName should be always set if finishReason is function_call
                if ($this->shouldCallTool($partialResponse->choices[0]->finishReason) && $toolsToCall !== []) {
                    foreach ($toolsToCall as $tool) {
                        if (is_string($tool['function']) && is_string($tool['id'])) {
                            $this->callFunction($tool['function'], $tool['arguments'], $tool['id']);
                        }
                    }
                    $newMessages = $this->getNewMessagesFromTools($messages);
                    if ($newMessages === []) {
                        break;
                    }
                    // We move to a non-streamed answer here. Maybe it could be improved
                    yield $this->generateChat($newMessages);
                }

                if (! is_null($partialResponse->choices[0]->finishReason)) {
                    break;
                }

                if ($partialResponse->choices[0]->delta->content === null) {
                    continue;
                }

                if ($partialResponse->choices[0]->delta->content === '') {
                    continue;
                }

                yield $partialResponse->choices[0]->delta->content;
            }
        };

        return Utils::streamFor($generator($stream));
    }

    /**
     * @param  Message[]  $messages
     * @return array<string, mixed>
     */
    private function getOpenAiArgs(array $messages): array
    {
        // The system message should be the first
        $finalMessages = [];
        if (isset($this->systemMessage)) {
            $finalMessages[] = $this->systemMessage;
        }

        $finalMessages = array_merge($finalMessages, $messages);

        $openAiArgs = $this->modelOptions;

        $openAiArgs = [...$openAiArgs, 'model' => $this->model, 'messages' => $finalMessages];

        if ($this->tools !== []) {
            $openAiArgs['tools'] = ToolFormatter::formatFunctionsToOpenAITools($this->tools);
        }

        if ($this->requiredFunction instanceof FunctionInfo) {
            $openAiArgs['tool_choice'] = ToolFormatter::formatToolChoice($this->requiredFunction);
        }

        $this->argsLog[] = $openAiArgs;

        return $openAiArgs;
    }

    /**
     * @throws \JsonException
     */
    private function handleTools(CreateResponse $answer): void
    {
        /** @var CreateResponseToolCall $toolCall */
        foreach ($answer->choices[0]->message->toolCalls as $toolCall) {
            $functionName = $toolCall->function->name;
            $arguments = $toolCall->function->arguments;

            $this->callFunction($functionName, $arguments, $toolCall->id);
        }
    }

    /**
     * @throws Exception
     */
    private function getFunctionInfoFromName(string $functionName, string $toolCallId): FunctionInfo
    {
        foreach ($this->tools as $function) {
            if ($function->name === $functionName) {
                return $function->cloneWithId($toolCallId);
            }
        }

        throw new Exception("OpenAI tried to call $functionName which doesn't exist");
    }

    private function callFunction(string $functionName, string $argumentsString, string $toolCallId): void
    {
        $arguments = $argumentsString !== '' && $argumentsString !== '0' ? json_decode($argumentsString, true, 512, JSON_THROW_ON_ERROR) : [];
        $functionToCall = $this->getFunctionInfoFromName($functionName, $toolCallId);
        $return = $functionToCall->instance->{$functionToCall->name}(...$arguments);
        $this->functionsCalled[] = new CalledFunction($functionToCall, $arguments, $return, $toolCallId);
        $this->lastFunctionCalled = $functionToCall;
    }

    /**
     * @param  Message[]  $messages
     */
    private function generateResponseFromMessages(array $messages): CreateResponse
    {
        $openAiArgs = $this->getOpenAiArgs($messages);

        $this->logger->debug('Calling Chat::create', [
            'chat' => self::class,
            'args' => $openAiArgs,
        ]);

        $answer = $this->client->chat()->create($openAiArgs);

        $this->logger->debug('Received Chat::create answer', [
            'chat' => self::class,
            'args' => $openAiArgs,
            'answer' => $answer,
        ]);

        $this->lastResponse = $answer;
        $this->totalTokens += $answer->usage->totalTokens ?? 0;

        return $answer;
    }

    private function responseToString(CreateResponse $answer): string
    {
        return $answer->choices[0]->message->content ?? '';
    }

    /**
     * @return array<FunctionInfo>
     *
     * @throws Exception
     */
    private function getToolsToCall(CreateResponse $answer): array
    {
        $functionInfos = [];
        /** @var CreateResponseToolCall $toolCall */
        foreach ($answer->choices[0]->message->toolCalls as $toolCall) {
            $functionName = $toolCall->function->name;
            $arguments = $toolCall->function->arguments;
            $functionInfo = $this->getFunctionInfoFromName($functionName, $toolCall->id);
            $functionInfo->jsonArgs = $arguments;

            $functionInfos[] = $functionInfo;
        }

        return $functionInfos;
    }

    private function shouldCallTool(?string $finishReason): bool
    {
        return $finishReason === 'function_call' || $finishReason === 'tool_calls';
    }

    /**
     * @param  Message[]  $messages
     * @return Message[]
     *
     * @throws \JsonException
     */
    public function getNewMessagesFromTools(array $messages): array
    {
        $toolsCalls = [];
        $toolsOutput = [];
        /** @var CalledFunction $functionCalled */
        foreach ($this->functionsCalled as $functionCalled) {
            if ($functionCalled->return) {
                $toolsOutput[] = Message::toolResult($functionCalled->return, $functionCalled->tool_call_id);
            }
            if ($functionCalled->tool_call_id) {
                $toolsCalls[] = new ToolCall($functionCalled->tool_call_id, $functionCalled->definition->name, json_encode($functionCalled->arguments, JSON_THROW_ON_ERROR));
            }
        }

        if ($toolsOutput === []) {
            return [];
        }

        $messages[] = Message::assistantAskingTools($toolsCalls);

        return array_merge($messages, $toolsOutput);
    }
}
