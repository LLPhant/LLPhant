<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\Message;
use Psr\Http\Message\StreamInterface;

interface ChatSessionInterface
{
    public function addMessage(Message $message): void;

    public function getHistoryAsString(): string;

    /**
     * @return Message[]
     */
    public function getHistory(): array;

    public function wrapAnswerStream(StreamInterface $stream): StreamInterface;
}
