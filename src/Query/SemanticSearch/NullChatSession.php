<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\Message;
use Psr\Http\Message\StreamInterface;

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
     * {@inheritDoc}
     */
    public function getHistory(): array
    {
        return [];
    }

    public function wrapAnswerStream(StreamInterface $stream): StreamInterface
    {
        return $stream;
    }
}
