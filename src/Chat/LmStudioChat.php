<?php

declare(strict_types=1);

namespace LLPhant\Chat;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\CalledFunction\CalledFunction;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\ToolCall;
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

    /** @var FunctionInfo[] */
    private array $tools = [];

    /** @var CalledFunction[] */
    public array $functionsCalled = [];

    private Client $client;

    public function __construct(
        protected LmStudioConfig $config,
        private readonly LoggerInterface $logger = new NullLogger(),
        ?Client $client = null
    ) {
        if ($config->model === '') {
            throw new MissingParameterException('You need to specify a model for LMStudio');
        }

        if (! $client instanceof \GuzzleHttp\Client) {
            $options = [
                'base_uri' => $config->url,
                'timeout' => $config->timeout,
                'connect_timeout' => $config->timeout,
                'read_timeout' => $config->timeout,
            ];

            if (! empty($config->apiKey)) {
                $options['headers'] = ['Authorization' => 'Bearer '.$config->apiKey];
            }

            $this->client = new Client($options);
        } else {
            $this->client = $client;
        }

        $this->modelOptions = $config->modelOptions;
    }

    // =================================================================================================================
    // CORE METHODS
    // =================================================================================================================

    public function generateText(string $prompt): string
    {
        $result = $this->generateTextOrReturnFunctionCalled($prompt);
        if (is_array($result)) {
            throw new \Exception('Function call returned from generateText. Use generateChat for tool use.');
        }

        return $result;
    }

    public function generateTextOrReturnFunctionCalled(string $prompt): array|string
    {
        return $this->generateChatOrReturnFunctionCalled([Message::user($prompt)]);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChat(array $messages): string
    {
        $result = $this->generateChatOrReturnFunctionCalled($messages);

        if (is_array($result)) {
            $messages[] = $this->assistantAskingFunctions($result);
            foreach ($result as $functionToCall) {
                $toolResult = $functionToCall->call();
                $toolResultMessage = Message::toolResult($toolResult, $functionToCall->getToolCallId());
                $messages[] = $toolResultMessage;
            }

            return $this->generateChat($messages);
        }

        return $result;
    }

    /**
     * @param  Message[]  $messages
     * @return string|FunctionInfo[]
     */
    public function generateChatOrReturnFunctionCalled(array $messages): array|string
    {
        $stream = false;

        $params = $this->createParameters($messages, $stream);

        $response = $this->sendRequest('POST', 'v1/chat/completions', $params, $stream);

        $contents = $response->getBody()->getContents();
        $this->logger->debug($contents);
        $json = Utility::decodeJson($contents);

        if (! isset($json['choices']) || empty($json['choices'])) {
            $this->logger->error('❌ LM Studio response missing choices array');
            $this->logger->error('   Response: '.$contents);
            throw new \Exception('Invalid LM Studio response: no choices returned');
        }

        $message = $json['choices'][0]['message'] ?? ['content' => ''];

        if (\array_key_exists('tool_calls', $message) && ! empty($message['tool_calls'])) {
            $result = [];
            foreach ($message['tool_calls'] as $toolCall) {
                $functionName = $toolCall['function']['name'];
                $functionInfo = $this->getFunctionInfoFromName($functionName);
                $functionInfo->jsonArgs = $toolCall['function']['arguments'];
                $functionInfo->setToolCallId($toolCall['id'] ?? null);
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
        return $this->generateChatStream([Message::user($prompt)]);
    }

    /**
     * @param  Message[]  $messages
     */
    public function generateChatStream(array $messages): StreamInterface
    {
        $stream = true;

        $params = $this->createParameters($messages, $stream);

        $response = $this->sendRequest('POST', 'v1/chat/completions', $params, $stream);

        $generator = function (StreamInterface $stream) {
            $buffer = '';
            while (! $stream->eof()) {
                $buffer .= $stream->read(1024);

                while (($pos = \strpos($buffer, "\n")) !== false) {
                    $line = \trim(substr($buffer, 0, $pos));
                    $buffer = \substr($buffer, $pos + 1);

                    if ($line === '' || ! \str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = \trim(\substr($line, 5));

                    if ($data === '[DONE]') {
                        return;
                    }

                    $json = \json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                    if (! $json) {
                        continue;
                    }

                    $delta = $json['choices'][0]['delta']['content'] ?? null;
                    if ($delta !== null && $delta !== '') {
                        yield $delta;
                    }
                }
            }
        };

        return Utils::streamFor($generator($response->getBody()));
    }

    // =================================================================================================================
    // CONFIGURATION & UTILITY METHODS
    // =================================================================================================================

    public function setSystemMessage(string $message): void
    {
        $this->systemMessage = Message::system($message);
    }

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
     * @param  array<string|mixed>  $json
     *
     * @throws HttpException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest(string $method, string $path, array $json, bool $stream): ResponseInterface
    {
        $this->logger->debug('Calling '.$method.' '.$path, ['chat' => self::class, 'params' => $json]);

        $requestOptions = ['json' => $json];
        $requestOptions['stream'] = $stream;

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
     * @return array<int|string, mixed>
     */
    protected function prepareMessages(array $messages): array
    {
        /** @var array<int|string, mixed> $responseMessages */
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

            if ($msg->role === ChatRole::Tool) {
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

    private function getFunctionInfoFromName(string $functionName): FunctionInfo
    {
        foreach ($this->tools as $function) {
            if ($function->name === $functionName) {
                return $function;
            }
        }

        throw new \Exception("AI tried to call $functionName which doesn't exist");
    }

    /**
     * @param  FunctionInfo[]  $functionInfos
     */
    private function assistantAskingFunctions(array $functionInfos): Message
    {
        $message = Message::assistant(null);

        $message->tool_calls = ToolCall::fromFunctionInfos($functionInfos);

        return $message;
    }

    /**
     * @param  Message[]  $messages
     * @return array<string, mixed>
     **/
    public function createParameters(array $messages, bool $stream): array
    {
        $params = [
            ...$this->modelOptions,
            'model' => $this->config->model,
            'messages' => $this->prepareMessages($messages),
            'stream' => $stream,
        ];

        if (! empty($this->tools)) {
            $params['tools'] = ToolFormatter::formatFunctionsToOpenAITools($this->tools);
        }

        return $params;
    }
}
