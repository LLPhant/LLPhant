<?php

declare(strict_types=1);

namespace LLPhant;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use LLPhant\Chat\Enums\MistralAIChatModel;
use LLPhant\Chat\MistralJsonResponseModifier;
use LLPhant\Exception\MissingParameterException;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use OpenAI\Factory;
use Psr\Http\Client\ClientInterface;

/**
 * @phpstan-import-type ModelOptions from OpenAIConfig
 */
class MistralAIConfig extends OpenAIConfig
{
    /**
     * @param  ModelOptions  $modelOptions
     */
    public function __construct(
        string $url = 'https://api.mistral.ai/v1',
        ?string $model = null,
        ?string $apiKey = null,
        ?ClientContract $client = null,
        array $modelOptions = []
    ) {
        $model ??= MistralAIChatModel::large->value;
        $apiKey ??= (getenv('MISTRAL_API_KEY') ?: null);
        if (! $apiKey) {
            throw new MissingParameterException('You have to provide a MISTRAL_API_KEY env var to request Mistral AI.');
        }
        if (! $client instanceof Client) {
            $clientFactory = new Factory();
            $client = $clientFactory
                ->withApiKey($apiKey)
                ->withBaseUri($url)
                ->withHttpClient($this->createMistralClient())
                ->make();
        }
        parent::__construct($apiKey, $url, $model, $client, $modelOptions);
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
