<?php

declare(strict_types=1);

namespace LLPhant;

class AnthropicConfig
{
    final public const CLAUDE_3_HAIKU = 'claude-3-haiku-20240307';

    final public const CLAUDE_3_5_SONNET = 'claude-3-5-sonnet-20240620';

    final public const CLAUDE_3_5_SONNET_20241022 = 'claude-3-5-sonnet-20241022';

    final public const CLAUDE_3_SONNET = 'claude-3-sonnet-20240229';

    final public const CLAUDE_3_OPUS = 'claude-3-opus-20240229';

    private const CURRENT_VERSION = '2023-06-01';

    public readonly string $apiKey;

    /**
     * @param  array<string, mixed>  $modelOptions
     */
    public function __construct(
        public readonly string $url = 'https://api.anthropic.com',
        public readonly string $model = self::CLAUDE_3_HAIKU,
        public readonly int $maxTokens = 1024,
        public readonly array $modelOptions = [],
        ?string $apiKey = null,
        public readonly string $version = self::CURRENT_VERSION,
    ) {
        $this->apiKey = $apiKey ?? (string) getenv('ANTHROPIC_API_KEY');
    }
}
