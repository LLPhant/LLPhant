<?php

namespace LLPhant\Chat;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use Psr\Http\Message\StreamInterface;

interface ChatInterface
{
    /**
     * Generates a text response from a prompt.
     * If tools are configured, the LLM may call them automatically.
     * The method will loop until a final text answer is produced.
     */
    public function generateText(string $prompt): string;

    /**
     * Generates a text response or returns a list of functions to be called.
     * This method stops at the first tool call suggested by the LLM,
     * allowing the developer to handle tool execution manually.
     *
     * @return string|FunctionInfo[]
     */
    public function generateTextOrReturnFunctionToCall(string $prompt): string|array;

    public function generateStreamOfText(string $prompt): StreamInterface;

    /**
     * Generates a chat response from an array of messages.
     * If tools are configured, the LLM may call them automatically.
     * The method will loop until a final text answer is produced.
     *
     * @param  Message[]  $messages
     */
    public function generateChat(array $messages): string;

    /**
     * Generates a chat response or returns a list of functions to be called.
     * This method stops at the first tool call suggested by the LLM,
     * allowing the developer to handle tool execution manually.
     *
     * @param  Message[]  $messages
     * @return string|FunctionInfo[]
     */
    public function generateChatOrReturnFunctionToCall(array $messages): string|array;

    /** @param  Message[]  $messages */
    public function generateChatStream(array $messages): StreamInterface;

    public function setSystemMessage(string $message): void;

    /** @param  FunctionInfo[]  $tools */
    public function setTools(array $tools): void;

    public function addTool(FunctionInfo $functionInfo): void;

    /** @param  FunctionInfo[]  $functions */
    public function setFunctions(array $functions): void;

    public function addFunction(FunctionInfo $functionInfo): void;

    public function setModelOption(string $option, mixed $value): void;
}
