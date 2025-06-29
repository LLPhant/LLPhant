<?php

namespace LLPhant\Chat;

use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Chat\Enums\MistralAIChatModel;
use LLPhant\OpenAIConfig;
use OpenAI\Client;
use OpenAI\Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

use function getenv;

class MistralAIChat extends OpenAIChat
{
    private const BASE_URL = 'api.mistral.ai/v1';

    public function __construct(
        ?OpenAIConfig $config = null,
        ?LoggerInterface $logger = null,
    ) {
        if (! $config instanceof OpenAIConfig) {
            $config = new OpenAIConfig();
        }

        if (! $config->client instanceof Client) {
            $apiKey = $config->apiKey ?? getenv('MISTRAL_API_KEY');
            if (! $apiKey) {
                throw new Exception('You have to provide a MISTRAL_API_KEY env var to request Mistral AI.');
            }

            $clientFactory = new Factory();
            $config->client = $clientFactory
                ->withApiKey($apiKey)
                ->withBaseUri(self::BASE_URL)
                ->withHttpClient($this->createMistralClient())
                ->make();
        }

        $config->model ??= MistralAIChatModel::large->value;
        parent::__construct($config, $logger);
    }

    private function createMistralClient(): ClientInterface
    {
        $httpClient = Psr18ClientDiscovery::find();
        $factory = new Psr17Factory();

        return new MistralJsonResponseModifier($httpClient, $factory);
    }
}
