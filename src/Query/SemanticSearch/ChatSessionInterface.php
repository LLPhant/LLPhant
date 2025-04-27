<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\Message;

interface ChatSessionInterface
{

    public function addMessage(Message $message): void;

    public function getHistoryAsString(): string;

    /**
     * @return Message[]
     */
    public function getHistory(): array;
}
