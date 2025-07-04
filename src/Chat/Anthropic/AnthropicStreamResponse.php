<?php

namespace LLPhant\Chat\Anthropic;

use LLPhant\Exception\FormatException;
use LLPhant\Exception\HttpException;
use LLPhant\Utility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AnthropicStreamResponse
{
    final public const DATA_PREFIX = 'data: ';

    use AnthropicTotalTokensTrait;

    public function __construct(
        protected ResponseInterface $response,
        private readonly StreamInterface $finalStream,
    ) {
    }

    /**
     * @throws FormatException
     * @throws HttpException
     */
    public function getStream(): StreamInterface
    {
        while (! $this->response->getBody()->eof()) {
            $line = $this->readLine($this->response->getBody());

            if (! str_starts_with($line, self::DATA_PREFIX)) {
                continue;
            }

            $data = str_replace(self::DATA_PREFIX, '', $line);

            $json = Utility::decodeJson(str_replace('data:', '', $data));

            if (isset($json['error'])) {
                throw new HttpException($json['error']);
            }

            $type = $json['type'];

            $this->addUsedTokens($json);

            if ($type === 'content_block_delta' && $json['delta']['type'] === 'text_delta') {
                $this->finalStream->write($json['delta']['text']);
            }

            if ($type === 'message_stop') {
                break;
            }
        }

        $this->finalStream->rewind();

        return $this->finalStream;
    }

    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (! $stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }
}
