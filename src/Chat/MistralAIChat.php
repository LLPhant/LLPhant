<?php

namespace LLPhant\Chat;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use LLPhant\Chat\Enums\MistralAIChatModel;
use LLPhant\Exception\MissingParameterException;
use LLPhant\MistralAIConfig;
use OpenAI\Client;
use OpenAI\Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MistralAIChat extends OpenAIChat
{
    public function __construct(MistralAIConfig $config = new MistralAIConfig(), LoggerInterface $logger = new NullLogger())
    {
        $config->model ??= MistralAIChatModel::large->value;
        if (! $config->client instanceof Client) {
            if (! $config->apiKey) {
                throw new MissingParameterException('You have to provide a OPENAI_API_KEY env var to request OpenAI.');
            }
            if (! $config->url) {
                throw new MissingParameterException('You have to provide an url o to set OPENAI_BASE_URL env var to request OpenAI.');
            }
            $clientFactory = new Factory();
            $config->client = $clientFactory
                ->withApiKey($config->apiKey)
                ->withBaseUri($config->url)
                ->withHttpClient($this->createMistralClient())
                ->make();
        }
        parent::__construct($config, $logger);
    }

    private function createMistralClient(): ClientInterface
    {
        $stack = HandlerStack::create();
        $stack->push(MistralJsonResponseModifier::createResponseModifier());

        return new GuzzleClient([
            'handler' => $stack,
        ]);
    }
}
