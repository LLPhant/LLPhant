<?php

namespace LLPhant\Chat;

use Exception;
use Gemini\Enums\ModelType;
use LLPhant\GeminiConfig;

/**
 * GeminiProChat class.
 */
class GeminiProChat extends GeminiChat
{
    /**
     * @param GeminiConfig|null $config The configuration options for the chat.
     * @throws Exception
     */
    public function __construct(?GeminiConfig $config = null)
    {
        parent::__construct($config);
        $this->model = $config->model ?? ModelType::GEMINI_PRO->value;
    }
}
