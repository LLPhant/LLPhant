<?php

declare(strict_types=1);

namespace LLPhant\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\CalledFunction\CalledFunction;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\ToolFormatter;
use LLPhant\Chat\Vision\VisionMessage;
use LLPhant\Exception\HttpException;
use LLPhant\Exception\MissingParameterException;
use LLPhant\LmStudioConfig;
use LLPhant\Utility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LmStudioChat implements ChatInterface
{
    private ?Message $systemMessage = null;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    public Client $client;

    /** @var FunctionInfo[] */
    private array $tools = [];

    /** @var CalledFunction[] */
    public array $functionsCalled = [];

    public function __construct(
        protected LmStudioConfig $config,
        private readonly LoggerInterface $logger = new NullLogger(),
        ?Client $client = null
    ) {
        if ($config->model === '') {
            throw new MissingParameterException('You need to specify a model for LMStudio');
        }

        if ($client === null) {
            $options = [
                'base_uri' => $config->url,
                'timeout' => $config->timeout,
                'connect_timeout' => $config->timeout,
                'read_timeout' => $config->timeout,
            ];

            if (! empty($config->apiKey)) {
                $options['headers'] = ['Authorization' => 'Bearer '.$config->apiKey];
            }

            $client = new Client($options);
        }

        $this->client = $client;
        $this->modelOptions = $config->modelOptions;
    }

    // =================================================================================================================
    // CORE METHODS
    // =================================================================================================================

    public function generateText(string $prompt): string
    {
        return $this->generateChat([Message::user($prompt)]);
    }

    /**
     * @return string|FunctionInfo[]
     */
    public function generateTextOrReturnFunctionToCall(string $prompt): string|array
    {
        return $this->generateChatOrReturnFunctionToCall([Message::user($prompt)]);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChat(array $messages): string
    {
        $this->functionsCalled = [];

        return $this->generateChatRecursive($messages);
    }

    /**
     * @param  Message[]  $messages
     * @return string|FunctionInfo[]
     */
    public function generateChatOrReturnFunctionToCall(array $messages): string|array
    {
        $this->functionsCalled = [];

        $message = $this->requestChatCompletion($messages);

        if (\array_key_exists('tool_calls', $message) && ! empty($message['tool_calls'])) {
            $result = [];
            foreach ($message['tool_calls'] as $toolCall) {
                $functionInfo = $this->getFunctionInfoFromName($toolCall['function']['name']);
                if (isset($toolCall['id'])) {
                    $functionInfo = $functionInfo->cloneWithId($toolCall['id']);
                }
                $functionInfo->jsonArgs = $toolCall['function']['arguments'];
                $result[] = $functionInfo;
            }

            return $result;
        }

        return $message['content'] ?? '';
    }

    // =================================================================================================================
    // STREAMING METHODS (Basic stub implementation to satisfy the interface)
    // =================================================================================================================

    public function generateStreamOfText(string $prompt): StreamInterface
    {
        $result = $this->generateText($prompt);

        return Utils::streamFor($result);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateStreamOfChat(array $messages): StreamInterface
    {
        $result = $this->generateChat($messages);

        return Utils::streamFor($result);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChatStream(array $messages): StreamInterface
    {
        $result = $this->generateChat($messages);

        return Utils::streamFor($result);
    }

    /**
     * @param  Message[]  $messages
     * @return StreamInterface|FunctionInfo[]
     */
    public function generateStreamOfChatOrReturnFunctionToCall(array $messages): StreamInterface|array
    {
        $result = $this->generateChatOrReturnFunctionToCall($messages);

        if (is_array($result)) {
            return $result;
        }

        return Utils::streamFor($result);
    }

    // =================================================================================================================
    // EMBEDDING METHOD
    // =================================================================================================================

    /**
     * @return float[]
     */
    public function getEmbedding(string $text, string $model = 'default-embed'): array
    {
        $params = [
            'model' => $model,
            'input' => $text,
        ];

        $response = $this->sendRequest('POST', 'v1/embeddings', $params);
        $json = Utility::decodeJson($response->getBody()->getContents());

        return $json['data'][0]['embedding'] ?? [];
    }

    // =================================================================================================================
    // CONFIGURATION & UTILITY METHODS
    // =================================================================================================================

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

    /**
     * @param  FunctionInfo[]  $functions
     */
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

    public function lastFunctionCalled(): ?CalledFunction
    {
        if ($this->functionsCalled === []) {
            return null;
        }

        return $this->functionsCalled[count($this->functionsCalled) - 1];
    }

    // =================================================================================================================
    // PROTECTED METHODS
    // =================================================================================================================

    /**
     * @param  mixed[]  $json
     */
    protected function sendRequest(string $method, string $path, array $json): ResponseInterface
    {
        $this->logger->debug('Calling '.$method.' '.$path, ['chat' => self::class, 'params' => $json]);

        $requestOptions = ['json' => $json];
        if (isset($json['stream']) && $json['stream']) {
            $requestOptions['stream'] = true;
        }

        $response = $this->client->request($method, $path, $requestOptions);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new HttpException(sprintf(
                'HTTP error from LMStudio (%d): %s',
                $status,
                $response->getBody()->getContents()
            ));
        }

        return $response;
    }

    /**
     * @param  Message[]  $messages
     * @return mixed[]
     */
    protected function prepareMessages(array $messages): array
    {
        $responseMessages = [];
        if (isset($this->systemMessage->role)) {
            $responseMessages[] = [
                'role' => $this->systemMessage->role,
                'content' => $this->systemMessage->content,
            ];
        }

        foreach ($messages as $msg) {
            $responseMessage = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];

            if ($msg->role === ChatRole::Assistant && ! empty($msg->tool_calls)) {
                $responseMessage['tool_calls'] = $msg->tool_calls;
            }

            if ($msg->role === ChatRole::Tool && isset($msg->tool_call_id)) {
                $responseMessage['tool_call_id'] = $msg->tool_call_id;
            }

            if ($msg instanceof VisionMessage) {
                $responseMessage['images'] = [];
                foreach ($msg->images as $image) {
                    $responseMessage['images'][] = $image->getBase64($this->client);
                }
            }

            $responseMessages[] = $responseMessage;
        }

        return $responseMessages;
    }

    /**
     * @param  Message[]  $messages
     */
    private function generateChatRecursive(array $messages): string
    {
        $message = $this->requestChatCompletion($messages);

        if (\array_key_exists('tool_calls', $message) && ! empty($message['tool_calls'])) {
            /** @var Message[] $toolsOutput */
            $toolsOutput = [];
            foreach ($message['tool_calls'] as $toolCall) {
                $arguments = Utility::decodeJson($toolCall['function']['arguments']);
                $toolResult = $this->callFunction($toolCall['function']['name'], $arguments, $toolCall['id'] ?? null);
                $toolsOutput[] = Message::toolResult($toolResult, $toolCall['id'] ?? null);
            }

            $assistantMessage = Message::assistant($message['content'] ?? null);
            $assistantMessage->tool_calls = $message['tool_calls'];

            return $this->generateChatRecursive(\array_merge($messages, [$assistantMessage], $toolsOutput));
        }

        return $message['content'] ?? '';
    }

    /**
     * @param  Message[]  $messages
     * @return array<string, mixed>
     */
    private function requestChatCompletion(array $messages): array
    {
        $params = [
            ...$this->modelOptions,
            'model' => $this->config->model,
            'messages' => $this->prepareMessages($messages),
            'stream' => false,
        ];

        if (! empty($this->tools)) {
            $params['tools'] = ToolFormatter::formatFunctionsToOpenAITools($this->tools);
        }

        $response = $this->sendRequest('POST', 'v1/chat/completions', $params);
        $contents = $response->getBody()->getContents();
        $this->logger->debug($contents);
        $json = Utility::decodeJson($contents);

        if (! isset($json['choices']) || empty($json['choices'])) {
            $this->logger->error('❌ LM Studio response missing choices array');
            $this->logger->error('   Response: '.$contents);
            throw new \Exception('Invalid LM Studio response: no choices returned');
        }

        return $json['choices'][0]['message'] ?? ['content' => ''];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callFunction(string $functionName, array $arguments, ?string $toolCallId): mixed
    {
        $functionToCall = $this->getFunctionInfoFromName($functionName);
        $return = $functionToCall->callWithArguments($arguments);

        $normalizedReturn = $return === null ? null : Message::normalizeContent($return);
        $this->functionsCalled[] = new CalledFunction($functionToCall, $arguments, $normalizedReturn, $toolCallId);

        return $return;
    }

    private function getFunctionInfoFromName(string $functionName): FunctionInfo
    {
        foreach ($this->tools as $function) {
            if ($function->name === $functionName) {
                return $function;
            }
        }

        throw new \Exception("AI tried to call $functionName which doesn't exist");
    }
}
