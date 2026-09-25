<?php

namespace LLPhant\Query\SemanticSearch;

use JsonSerializable;
use LLPhant\Chat\Message;
use Psr\Http\Message\StreamInterface;

class ChatSession implements ChatSessionInterface, JsonSerializable
{
    /**
     * @var Message[]
     */
    protected array $messages = [];

    private ?ChatSessionStream $chatSessionStream = null;

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function getHistory(): array
    {
        if ($this->chatSessionStream instanceof ChatSessionStream) {
            $this->addMessage(Message::assistant($this->chatSessionStream->getAnswer()));
            $this->chatSessionStream = null;
        }

        return $this->messages;
    }

    public function getHistoryAsString(): string
    {
        if ($this->getHistory() === []) {
            return '';
        }

        return implode("\n", $this->getHistory());
    }

    public function wrapAnswerStream(StreamInterface $stream): StreamInterface
    {
        $this->chatSessionStream = new ChatSessionStream($stream);

        return $this->chatSessionStream;
    }

    public function jsonSerialize(): mixed
    {
        return \array_map(fn (Message $message): array => $message->jsonSerialize(), $this->messages);
    }

    public static function fromJson(string $jsonString): ChatSession
    {
        $result = new ChatSession();

        $messages = \json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        foreach ($messages as $message) {
            $result->addMessage(Message::fromJson($message));
        }

        return $result;
    }
}
