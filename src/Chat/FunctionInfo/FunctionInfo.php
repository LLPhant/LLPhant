<?php

namespace LLPhant\Chat\FunctionInfo;

use LLPhant\Chat\Message;

class FunctionInfo
{
    public string $jsonArgs;

    private ?string $toolCallId = null;

    /**
     * @param  Parameter[]  $parameters
     * @param  Parameter[]  $requiredParameters
     */
    public function __construct(public string $name, public mixed $instance, public string $description, public array $parameters, public array $requiredParameters = [])
    {
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callWithArguments(array $arguments): mixed
    {
        return $this->instance->{$this->name}(...$arguments);
    }

    public function getToolCallId(): ?string
    {
        return $this->toolCallId;
    }

    public function setToolCallId(?string $toolCallId): void
    {
        $this->toolCallId = $toolCallId;
    }

    public function cloneWithId(string $toolCallId): FunctionInfo
    {
        $result = clone $this;
        $result->toolCallId = $toolCallId;

        return $result;
    }

    /**
     * @return Message[]
     */
    public function callAndReturnAsOpenAIMessages(): array
    {
        $functionResult = $this->call();

        return $this->asOpenAIMessages($functionResult);
    }

    public function asToolCallObject(): ToolCall
    {
        return new ToolCall(
            $this->toolCallId ?? throw new \RuntimeException('A tool call id is needed'),
            $this->name,
            $this->jsonArgs ?? throw new \RuntimeException('A tool args are needed'));
    }

    /**
     * @throws \JsonException
     */
    public function call(): mixed
    {
        $arguments = json_decode($this->jsonArgs, true, 512, JSON_THROW_ON_ERROR);

        return $this->instance->{$this->name}(...$arguments);
    }

    /**
     * @return Message[]
     */
    public function asOpenAIMessages(mixed $functionResult): array
    {
        return [
            Message::assistantAskingTools([$this->asToolCallObject()]),
            Message::toolResult(
                $functionResult,
                $this->toolCallId
            )];
    }
}
