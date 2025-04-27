<?php

namespace LLPhant\Query\SemanticSearch;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class ChatSessionStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private string $answer = '';

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
}
