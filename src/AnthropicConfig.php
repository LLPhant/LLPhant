<?php

declare(strict_types=1);

namespace LLPhant;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AnthropicConfig
{
    final public const CLAUDE_4_5_HAIKU = 'claude-haiku-4-5-20251001';

    /**
     * @param  array<string, mixed>  $modelOptions
     */
    public function __construct(
        public readonly string $model = self::CLAUDE_4_5_HAIKU,
        public readonly int $maxTokens = 1024,
        public readonly array $modelOptions = [],
        public readonly ?string $apiKey = null,
        public readonly ?ClientInterface $client = null,
        public readonly ?RequestFactoryInterface $requestFactory = null,
        public readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
    }
}
