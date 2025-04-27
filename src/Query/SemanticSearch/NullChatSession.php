<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\Message;
use LLPhant\Query\SemanticSearch\ChatSessionInterface;

class NullChatSession implements ChatSessionInterface
{
    public function addMessage(Message $message): void
    {
    }

    public function getHistoryAsString(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getHistory(): array
    {
        return [];
    }
}
