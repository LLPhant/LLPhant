<?php

namespace LLPhant\Chat;

use Exception;
use Gemini;
use Gemini\Contracts\Resources\GenerativeModelContract;
use Gemini\Contracts\ResponseContract;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Enums\ModelType;
use Gemini\Enums\Role;
use Gemini\Responses\StreamResponse;
use GuzzleHttp\Psr7\Utils;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\GeminiConfig;
use Gemini\Contracts\ClientContract;
use Psr\Http\Message\StreamInterface;

use function getenv;

/**
 * GeminiChat class.
 */
class GeminiChat implements ChatInterface
{
    /** @var ClientContract|null $client */
    private readonly ClientContract $client;

    /** @var string $model */
    private string $model;

    /** @var ResponseContract|null $lastResponse */
    private ?ResponseContract $lastResponse = null;

    /** @var int $totalTokens */
    private int $totalTokens = 0;

    /** @var array<string, mixed> $modelOptions */
    private array $modelOptions = [];

    /** @var Message $systemMessage */
    private Message $systemMessage;

    /** @var FunctionInfo[] $tools */
    private array $tools = [];

    /** @var FunctionInfo|null $lastFunctionCalled */
    public ?FunctionInfo $lastFunctionCalled = null;

    /**
     * @param GeminiConfig|null $config The configuration options for the chat.
     * @throws Exception If the API key is not provided
     */
    public function __construct(?GeminiConfig $config = null)
    {
        if ($config instanceof GeminiConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            $this->client = $this->buildClient($config);
        }

        $this->model = $config->model ?? ModelType::GEMINI_FLASH->value;
        $this->modelOptions = $config->modelOptions ?? [];
    }

    /**
     * Build the Gemini client.
     *
     * @param GeminiConfig|null $config The configuration for the Gemini API
     * @return ClientContract The Gemini client
     * @throws Exception
     */
    protected function buildClient(?GeminiConfig $config): ClientContract
    {
        $clientFactory = Gemini::factory();

        $apiKey = $config->apiKey ?? getenv('GEMINI_API_KEY');
        if (!$apiKey) {
            throw new Exception('You have to provide a GEMINI_API_KEY env var to request Gemini API.');
        }
        $clientFactory->withApiKey($apiKey);

        $baseUrl = $config->url ?? getenv('GEMINI_BASE_URL');
        if (!empty($baseUrl)) {
            $clientFactory->withBaseUrl($baseUrl);
        }

        return $clientFactory->make();
    }

    /**
     * @return string The model to use
     */
    protected function getModel(): string {
        return $this->model;
    }

    /**
     * @return GenerativeModelContract The generative model
     */
    protected function getGenerativeModel(): GenerativeModelContract {

        $generativeModel = $this->client->generativeModel($this->getModel());

        $generationConfig = $this->generationConfig();
        if (!empty($generationConfig)) {
            $generativeModel->withGenerationConfig($generationConfig);
        }

        return $generativeModel;
    }

    /**
     * @return GenerationConfig|null The generation configuration
     */
    protected function generationConfig(): ?GenerationConfig {

        if (empty($this->modelOptions)) {
            return null;
        }

        return new GenerationConfig(
            candidateCount: $this->modelOptions['candidateCount'] ?? 1,
            stopSequences: $this->modelOptions['stopSequences'] ?? [],
            maxOutputTokens: $this->modelOptions['maxOutputTokens'] ?? null,
            temperature: $this->modelOptions['temperature'] ?? null,
            topP: $this->modelOptions['topP'] ?? null,
            topK: $this->modelOptions['topK'] ?? null
        );
    }

    /**
     * @return ResponseContract|null The last response from the Gemini API
     */
    public function getLastResponse(): ?ResponseContract
    {
        return $this->lastResponse;
    }

    /**
     * @return int The total number of tokens used.
     */
    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * @param string $prompt The message to generate a response from
     * @return string The response from the Gemini API
     */
    public function generateText(string $prompt): string
    {
        $answer = $this->generateResponseFromMessage($prompt);

        return $this->responseToString($answer);
    }

    /**
     * @param string $prompt The message to generate a response from
     * @return string|FunctionInfo The response from the Gemini API or the function called
     */
    public function generateTextOrReturnFunctionCalled(string $prompt): string|FunctionInfo
    {
        $this->lastFunctionCalled = null;

        $message = $this->prepareMessage($prompt);
        $answer = $this->generateResponseFromMessage($message);

        $toolsToCall = $this->getToolsToCall($answer);

        foreach ($toolsToCall as $toolToCall) {
            $this->lastFunctionCalled = $toolToCall;
        }

        if ($this->lastFunctionCalled instanceof FunctionInfo) {
            return $this->lastFunctionCalled;
        }

        return $this->responseToString($answer);
    }

    /**
     * @param string $prompt The message to generate a response from
     * @return StreamInterface The streamed response
     */
    public function generateStreamOfText(string $prompt): StreamInterface
    {
        $message = $this->prepareMessage($prompt);
        $stream = $this->getGenerativeModel()->streamGenerateContent($message);

        return $this->createStreamedResponse($stream);
    }

    /**
     * @param Message[] $messages
     * @return string The response from the Gemini API
     */
    public function generateChat(array $messages): string
    {
        return $this->responseToString($this->createChatAnswer($messages));
    }

    /**
     * @param Message[] $messages
     * @return ResponseContract The response from the Gemini API
     */
    private function createChatAnswer(array $messages): ResponseContract
    {
        [$history, $message] = $this->prepareChatMessages($messages);
        $chat = $this->getGenerativeModel()->startChat(history: $history);

        return $chat->sendMessage($message);
    }

    /**
     * TODO: Gemini client does not support chat streaming, so currently implemented as a single response in a stream.
     *
     * @param Message[] $messages
     * @return StreamInterface The streamed response
     */
    public function generateChatStream(array $messages): StreamInterface
    {
        $answer = $this->generateChat($messages);

        $generator = function (array $stream) {
            foreach ($stream as $partialResponse) {
                yield $partialResponse;
            }
        };

        return Utils::streamFor($generator([$answer]));
    }

    /**
     * @param array $messages The messages to generate a response from
     * @return string|FunctionInfo The response from the Gemini API or the function called
     */
    public function generateChatOrReturnFunctionCalled(array $messages): string|FunctionInfo
    {
        $answer = $this->createChatAnswer($messages);

        $toolsToCall = $this->getToolsToCall($answer);

        $this->lastFunctionCalled = null;
        foreach ($toolsToCall as $toolToCall) {
            $this->lastFunctionCalled = $toolToCall;
        }

        if ($this->lastFunctionCalled instanceof FunctionInfo) {
            return $this->lastFunctionCalled;
        }

        return $this->responseToString($answer);
    }

    /**
     * TODO: This method has not yet been implemented, as google-gemini-php/gemini does not support function calls.
     *
     * @param ResponseContract $answer The response from the Gemini API
     * @return array<FunctionInfo>
     */
    private function getToolsToCall(ResponseContract $answer): array
    {
        return [];
    }

    /**
     * @param string $message The system message to attach to the response
     * @return void
     */
    public function setSystemMessage(string $message): void
    {
        $this->systemMessage = Message::system($message);
    }

    /**
     * @param FunctionInfo[] $tools
     * @return void
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
     * @param FunctionInfo[] $functions
     */
    public function setFunctions(array $functions): void
    {
        $this->tools = $functions;
    }

    /**
     * @deprecated Use addTool instead
     *
     * @param FunctionInfo $functionInfo The function to add
     */
    public function addFunction(FunctionInfo $functionInfo): void
    {
        $this->tools[] = $functionInfo;
    }

    /**
     * @param string $option The option to set
     * @param mixed $value
     * @return void
     */
    public function setModelOption(string $option, mixed $value): void
    {
        $this->modelOptions[$option] = $value;
    }

    /**
     * @param string $prompt The initial prompt to generate a response from
     * @return string Message with additional system instructions
     */
    protected function prepareMessage(string $prompt): string
    {
        if (!empty($this->systemMessage->content)) {
            $prompt = implode('. ', [$this->systemMessage->content, $prompt]);
        }

        return $prompt;
    }

    /**
     * @param Message[] $messages The messages to generate a response from
     * @return array The chat history and the message to send
     */
    protected function prepareChatMessages(array $messages): array
    {
        $history = []; $message = '';

        if (empty($messages)) {
            return [$history, $message];
        }

        $message = array_pop($messages)->content;

        // The system message is always the first message
        if (isset($this->systemMessage->role)) {
            $history[] = Content::parse(part: $this->systemMessage->content);
        }

        foreach ($messages as $historical_message) {
            $role = ($historical_message->role === ChatRole::System) ? Role::MODEL : Role::USER;
            $history[] = Content::parse(part: $historical_message->content, role: $role);
        }

        return [$history, $message];
    }

    /**
     * @param StreamResponse $stream Gemini stream response
     * @return StreamInterface The streamed response
     */
    private function createStreamedResponse(StreamResponse $stream): StreamInterface
    {
        $generator = function (StreamResponse $stream) {
            foreach ($stream as $partialResponse) {
                yield $partialResponse->text();
            }
        };

        return Utils::streamFor($generator($stream));
    }

    /**
     * @param string $message The message to generate a response from
     * @return ResponseContract The response from the Gemini API
     */
    private function generateResponseFromMessage(string $message): ResponseContract
    {
        $answer = $this->getGenerativeModel()->generateContent($message);

        $this->lastResponse = $answer;
        $this->totalTokens += $answer->usageMetadata->totalTokenCount ?? 0;

        return $answer;
    }

    /**
     * @param ResponseContract $answer The response from the Gemini API
     * @return string The response as a string
     */
    private function responseToString(ResponseContract $answer): string
    {
        return $answer->text();
    }
}
