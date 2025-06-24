<?php

namespace LLPhant\Audio;

use Exception;
use GuzzleHttp\Client;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;

class OpenAIAudio
{
    private readonly ClientContract $client;

    public string $model;

    /** @var array<string, mixed> */
    private array $modelOptions = [];

    public function __construct(?OpenAIConfig $config = null)
    {
        if ($config instanceof OpenAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;
        } else {
            $apiKey = $config->apiKey ?? getenv('OPENAI_API_KEY');
            if (! $apiKey) {
                throw new Exception('You have to provide a OPENAI_API_KEY env var to request OpenAI .');
            }

            $factory = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
                ->withBaseUri($config->url ?? (getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1'));

            if ($defaultTimeout = getenv('OPENAI_DEFAULT_TIMEOUT')) {
                if (! is_numeric($defaultTimeout)) {
                    throw new Exception("The OPENAI_DEFAULT_TIMEOUT env var value should be numeric, you provide '$defaultTimeout'");
                }
                $factory->withHttpClient(new Client(['timeout' => $defaultTimeout]));
            }

            $this->client = $factory->make();
        }
        $this->model = $config->model ?? OpenAIAudioModel::Whisper1->value;
        // See https://platform.openai.com/docs/api-reference/audio/createTranscription for possible options
        $this->modelOptions = $config->modelOptions ?? [];
    }

    public function transcribe(string $fileName): Transcription
    {
        $response = $this->client->audio()->transcribe([...$this->modelOptions, 'model' => $this->model, 'file' => fopen($fileName, 'r'), 'response_format' => 'verbose_json']);

        return new Transcription($response->text, $response->language, $response->duration);
    }
}
