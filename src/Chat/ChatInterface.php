<?php

namespace LLPhant\Chat;

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use Psr\Http\Message\StreamInterface;

interface ChatInterface
{
    public function generateText(string $prompt): string;

    /**
     * @return string|FunctionInfo[]
     */
    public function generateTextOrReturnFunctionToCall(string $prompt): string|array;

    public function generateStreamOfText(string $prompt): StreamInterface;

    /** @param  Message[]  $messages */
    public function generateChat(array $messages): string;

    /**
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
