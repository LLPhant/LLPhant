<?php

namespace LLPhant\Chat;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\CalledFunction\CalledFunction;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\ToolCall;
use LLPhant\Chat\FunctionInfo\ToolFormatter;
use LLPhant\DeepseekConfig;
use LLPhant\Exception\HttpException;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DeepseekChat implements ChatInterface
{
    private const DEFAULT_URL = 'https://api.deepseek.com/v3';

    private ?Message $systemMessage = null;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    private readonly Client $client;

    private readonly LoggerInterface $logger;

    private readonly string $model;

    private readonly int $maxTokens;

    /** @var FunctionInfo[] */
    private array $tools = [];

    /** @var CalledFunction[] */
    public array $functionsCalled = [];

    public ?FunctionInfo $lastFunctionCalled = null;

    public function __construct(DeepseekConfig $config = new DeepseekConfig(), ?LoggerInterface $logger = null)
    {
        $this->modelOptions = $config->modelOptions;
        $this->model = $config->model;
        $this->maxTokens = $config->maxTokens;
        $this->logger = $logger ?: new NullLogger();

        if ($config->client instanceof Client) {
            $this->client = $config->client;
        } else {
            $apiKey = $config->apiKey ?? getenv('DEEPSEEK_API_KEY');
            if (! $apiKey) {
                throw new Exception('You have to provide a DEEPSEEK_API_KEY env var to request DeepSeek.');
            }

            $this->client = new Client([
                'base_uri' => $config->baseUrl ?? self::DEFAULT_URL,
                'timeout' => $config->timeout ?? 30,
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
    }

    public function generateText(string $prompt): string
    {
        return $this->generateChat([Message::user($prompt)]);
    }

    /** @param Message[] $messages */
    public function generateChat(array $messages): string
    {
        $params = $this->createParams($messages, false);

        $this->logger->debug('Calling DeepSeek chat completion', [
            'chat' => self::class,
            'params' => $params,
        ]);

        try {
            $response = $this->client->post('/chat/completions', [
                'json' => $params,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            $this->logger->debug('Received DeepSeek response', [
                'chat' => self::class,
                'response' => $responseData,
            ]);

            // Handle tool calls if present
            if (isset($responseData->choices[0]->message->tool_calls)) {
                $toolsOutput = [];
                foreach ($responseData->choices[0]->message->tool_calls as $toolCall) {
                    $functionName = $toolCall->function->name;
                    $toolResult = $this->callFunction($functionName, (string) $toolCall->function->arguments, $toolCall->id);
                    if (is_string($toolResult)) {
                        $toolsCalls[] = new ToolCall($toolCall->id, $toolCall->function->name, json_encode($toolCall->function->arguments, JSON_THROW_ON_ERROR));
                        $toolsOutput[] = Message::assistantAskingTools($toolsCalls);
                        $toolsOutput[] = Message::toolResult($toolResult, $toolCall->id);
                    }
                }

                if ($toolsOutput !== []) {
                    return $this->generateChat(array_merge($messages, $toolsOutput));
                }
            }

            // Extract the text content from the response
            if (isset($responseData->choices[0]->message->content)) {
                return $responseData->choices[0]->message->content;
            }

            throw new \RuntimeException('Invalid response format from DeepSeek API');
        } catch (Exception $e) {
            $this->logger->error('Error calling DeepSeek chat completion', [
                'chat' => self::class,
                'error' => $e->getMessage(),
            ]);
            throw new HttpException('Error calling DeepSeek chat completion: '.$e->getMessage(), 0, $e);
        }
    }

    public function generateStreamOfText(string $prompt): StreamInterface
    {
        return $this->generateChatStream([Message::user($prompt)]);
    }

    /** @param Message[] $messages */
    public function generateChatStream(array $messages): StreamInterface
    {
        $params = $this->createParams($messages, true);

        $this->logger->debug('Calling DeepSeek chat stream', [
            'chat' => self::class,
            'params' => $params,
        ]);

        try {
            $response = $this->client->post('/chat/completions', [
                'json' => $params,
                'stream' => true,
            ]);

            return Utils::streamFor($response->getBody());
        } catch (Exception $e) {
            $this->logger->error('Error calling DeepSeek chat stream', [
                'chat' => self::class,
                'error' => $e->getMessage(),
            ]);
            throw new HttpException('Error calling DeepSeek chat stream: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return string|FunctionInfo[]
     */
    public function generateTextOrReturnFunctionCalled(string $prompt): string|array
    {
        $answer = $this->generateText($prompt);

        if ($this->functionsCalled) {
            $allFunctions = [];
            foreach ($this->functionsCalled as $functionCalled) {
                $allFunctions[] = $functionCalled->definition;
            }

            return $allFunctions;
        }

        return $answer;
    }

    /**
     * @return string|FunctionInfo[]
     */
    public function generateChatOrReturnFunctionCalled(array $messages): string|array
    {
        $answer = $this->generateChat($messages);

        if ($this->functionsCalled) {
            $allFunctions = [];
            foreach ($this->functionsCalled as $functionCalled) {
                $allFunctions[] = $functionCalled->definition;
            }

            return $allFunctions;
        }

        return $answer;
    }

    public function setSystemMessage(string $message): void
    {
        $this->systemMessage = Message::system($message);
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

    /** @param FunctionInfo[] $functions */
    public function setFunctions(array $functions): void
    {
        $this->setTools($functions);
    }

    public function addFunction(FunctionInfo $functionInfo): void
    {
        $this->addTool($functionInfo);
    }

    public function setModelOption(string $option, mixed $value): void
    {
        $this->modelOptions[$option] = $value;
    }

    /**
     * @param  Message[]  $messages
     * @return array<string, mixed>
     */
    private function createParams(array $messages, bool $stream): array
    {
        $params = [
            ...$this->modelOptions,
            'model' => $this->model,
            'messages' => $this->prepareMessages($messages),
            'max_tokens' => $this->maxTokens,
            'stream' => $stream,
        ];

        if ($this->systemMessage instanceof Message) {
            $params['system'] = $this->systemMessage->content;
        }

        if ($this->tools !== []) {
            $params['tools'] = ToolFormatter::formatFunctionsToOpenAITools($this->tools);
        }

        return $params;
    }

    /**
     * @param  Message[]  $messages
     * @return array<Message>
     */
    private function prepareMessages(array $messages): array
    {
        $preparedMessages = [];

        if ($this->systemMessage instanceof Message) {
            $preparedMessages[] = Message::system($this->systemMessage->content);
        }

        foreach ($messages as $message) {
            $preparedMessages[] = $message;
        }

        return $preparedMessages;
    }

    /**
     * @throws Exception
     */
    private function callFunction(string $functionName, string $argumentsString, string $toolCallId): mixed
    {
        $functionInfo = $this->getFunctionInfoFromName($functionName);
        $functionInfo->jsonArgs = $argumentsString;
        $arguments = json_decode($argumentsString, true, 512, JSON_THROW_ON_ERROR);
        $this->lastFunctionCalled = $functionInfo;
        $result = $functionInfo->instance->{$functionName}(...$arguments);
        $this->functionsCalled[] = new CalledFunction($functionInfo, $arguments, $result, $toolCallId);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getFunctionInfoFromName(string $functionName): FunctionInfo
    {
        foreach ($this->tools as $tool) {
            if ($tool->name === $functionName) {
                return $tool;
            }
        }

        throw new \RuntimeException("Function $functionName not found");
    }
}
