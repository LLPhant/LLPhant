<?php

declare(strict_types=1);

namespace LLPhant;

class LmStudioConfig
{
  /**
   * Le nom du modèle à utiliser. Doit correspondre à un modèle chargé dans LM Studio.
   * Exemples: 'llama-2-7b-chat', 'mistral-7b-instruct', 'phi-2', etc.
   */
  public string $model = '';

  /**
   * L'URL de base de l'API de LM Studio, souvent /api/v0/ pour les endpoints
   * compatibles OpenAI (v1/chat/completions, v1/embeddings, etc.).
   */
  public string $url = 'http://localhost:1234/api/v0/';

  public bool $stream = false;

  public bool $formatJson = false;

  public ?float $timeout = null;

  public ?string $apiKey = null;

  /**
   * model options, example:
   * - temperature: Contrôle la créativité (0.0 à 1.0)
   * - max_tokens: Nombre maximum de tokens à générer
   * - top_p: Nucleus sampling
   * - frequency_penalty: Pénalité de répétition
   * - presence_penalty: Pénalité de présence
   *
   * @see https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-completion
   *
   * @var array<string, mixed>
   */
  public array $modelOptions = [];
}