<?php

namespace LLPhant\Chat\Anthropic;

trait AnthropicTotalTokensTrait
{
    private int $totalTokens = 0;

    /**
     * @param  array<string, mixed>  $json
     */
    protected function addUsedTokens(array $json): void
    {
        if (\array_key_exists('usage', $json)) {
            if (\array_key_exists('input_tokens', $json['usage'])) {
                $this->totalTokens += $json['usage']['input_tokens'];
            }
            if (\array_key_exists('output_tokens', $json['usage'])) {
                $this->totalTokens += $json['usage']['output_tokens'];
            }
        }
    }
}
