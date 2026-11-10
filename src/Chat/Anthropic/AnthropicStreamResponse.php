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

    private int $inputTokens = 0;

    private int $cacheCreationInputTokens = 0;

    private int $cacheReadInputTokens = 0;

    private int $outputTokens = 0;

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
                // The error event carries an object: {"type": "overloaded_error", "message": "..."}
                $error = $json['error'];
                throw new HttpException(\is_array($error)
                    ? sprintf('%s: %s', $error['type'] ?? 'error', $error['message'] ?? json_encode($error))
                    : (string) $error);
            }

            $type = $json['type'];

            $this->updateUsedTokens($json);

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
        return $this->inputTokens
            + $this->cacheCreationInputTokens
            + $this->cacheReadInputTokens
            + $this->outputTokens;
    }

    /**
     * Usage is cumulative and each counter is independent. message_start reports the input and
     * cache tokens under message.usage; message_delta reports the final output count and may
     * repeat only some of the other keys. A key that is absent leaves the counter already stored,
     * so the latest value reported for each counter wins.
     *
     * @param  array<string, mixed>  $json
     */
    private function updateUsedTokens(array $json): void
    {
        $usage = $json['usage'] ?? $json['message']['usage'] ?? null;
        if (! \is_array($usage)) {
            return;
        }

        if (\array_key_exists('input_tokens', $usage)) {
            $this->inputTokens = (int) $usage['input_tokens'];
        }
        if (\array_key_exists('cache_creation_input_tokens', $usage)) {
            $this->cacheCreationInputTokens = (int) $usage['cache_creation_input_tokens'];
        }
        if (\array_key_exists('cache_read_input_tokens', $usage)) {
            $this->cacheReadInputTokens = (int) $usage['cache_read_input_tokens'];
        }
        if (\array_key_exists('output_tokens', $usage)) {
            $this->outputTokens = (int) $usage['output_tokens'];
        }
    }
}
