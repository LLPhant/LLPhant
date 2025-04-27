<?php

namespace LLPhant\Chat;

use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\FunctionInfo\ToolCall;

class Message implements \Stringable
{
    public ChatRole $role;

    public string $content;

    public string $tool_call_id;

    public string $name;

    /**
     * @var ToolCall[]
     */
    public array $tool_calls;

    public function __toString(): string
    {
        return (string) "{$this->role->value}: {$this->content}";
    }

    public static function system(string $content): self
    {
        $message = new self();
        $message->role = ChatRole::System;
        $message->content = $content;

        return $message;
    }

    public static function user(string $content): self
    {
        $message = new self();
        $message->role = ChatRole::User;
        $message->content = $content;

        return $message;
    }

    /**
     * @param  ToolCall[]  $toolCalls
     */
    public static function assistantAskingTools(array $toolCalls): self
    {
        $message = new self();
        $message->role = ChatRole::Assistant;
        $message->tool_calls = $toolCalls;

        return $message;
    }

    public static function assistant(?string $content): self
    {
        $message = new self();
        $message->role = ChatRole::Assistant;
        $message->content = $content ?? '';

        return $message;
    }

    public static function functionResult(?string $content, string $name): self
    {
        $message = new self();
        $message->role = ChatRole::Function;
        $message->content = $content ?? '';
        $message->name = $name;

        return $message;
    }

    public static function toolResult(?string $content, ?string $toolCallId = null): self
    {
        $message = new self();
        $message->role = ChatRole::Tool;
        $message->content = $content ?? '';

        if ($toolCallId !== null) {
            $message->tool_call_id = $toolCallId;
        }

        return $message;
    }
}
