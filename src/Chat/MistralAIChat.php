<?php

namespace LLPhant\Chat;

use LLPhant\Chat\Enums\MistralAIChatModel;
use LLPhant\MistralAIConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MistralAIChat extends OpenAIChat
{
    public function __construct(MistralAIConfig $config = new MistralAIConfig(), LoggerInterface $logger = new NullLogger())
    {
        $config->model ??= MistralAIChatModel::large->value;
        parent::__construct($config, $logger);
    }
}
