<?php

namespace LLPhant\Embeddings\EmbeddingGenerator;

/**
 * Tokens billed for the embedding requests made by this generator, read from the provider answer.
 */
trait EmbeddingTotalTokensTrait
{
    private int $totalTokens = 0;

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * Ollama reports `prompt_eval_count`; OpenAI, Mistral, LM Studio and VoyageAI a `usage` member.
     *
     * @param  array<string, mixed>  $json  decoded response body
     */
    protected function addUsedTokens(array $json): void
    {
        $usage = \is_array($json['usage'] ?? null) ? $json['usage'] : [];

        $this->totalTokens += (int) ($usage['total_tokens'] ?? $usage['prompt_tokens'] ?? $json['prompt_eval_count'] ?? 0);
    }
}
