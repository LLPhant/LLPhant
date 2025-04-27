<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Message;

class ChatSession implements ChatSessionInterface
{

    /**
     * @var Message[]
     */
    protected $messages = [];

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function getHistory(): array
    {
        return $this->messages;
    }

    public function getHistoryAsString(): string
    {
        if (count($this->messages) === 0) {
            return '';
        }

        return implode("\n", $this->messages);
    }
}
