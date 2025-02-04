<?php

namespace LLPhant\Chat;

use LLPhant\Chat\Enums\OpenAIChatModel;
use LLPhant\OpenAIConfig;
use Psr\Log\LoggerInterface;

class GPT4Turbo extends OpenAIChat
{
    /**
     * @throws \Exception
     */
    public function __construct(?OpenAIConfig $config = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($config, $logger);
        $this->model = $config->model ?? OpenAIChatModel::Gpt4Turbo->value;
    }
}
