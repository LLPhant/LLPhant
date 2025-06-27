<?php

namespace LLPhant\Query\SemanticSearch;

use Psr\Http\Message\StreamInterface;

class ChatSessionStream implements \Stringable, StreamInterface
{
    private string $answer = '';

    public function __construct(private readonly StreamInterface $stream)
    {
    }

    public function getContents(): string
    {
        $contents = $this->stream->getContents();

        $this->answer .= $contents;

        return $contents;
    }

    public function read(int $length): string
    {
        $contents = $this->stream->read($length);

        $this->answer .= $contents;

        return $contents;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    // Decorated methods...
    public function __toString(): string
    {
        return (string) $this->stream;
    }

    public function close(): void
    {
        $this->stream->close();
    }

    /**
     * @return resource|null
     */
    public function detach(): mixed
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function write(string $string): int
    {
        return $this->stream->write($string);
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    /**
     * @return array|mixed|null
     */
    public function getMetadata(?string $key = null): mixed
    {
        return $this->stream->getMetadata($key);
    }
    // end decorated methods...
}
