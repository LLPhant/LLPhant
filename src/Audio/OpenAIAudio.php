<?php

namespace LLPhant\Audio;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
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

            if ($config?->timeout !== null) {
                $options = [
                    'timeout' => $config->timeout,
                    'connect_timeout' => $config->timeout,
                    'read_timeout' => $config->timeout,
                ];
                $factory->withHttpClient(new GuzzleClient($options));
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

    /**
     * Translate an audio into english
     *
     * @see https://platform.openai.com/docs/api-reference/audio/createTranslation
     */
    public function translate(string $fileName, ?string $prompt = null): string
    {
        $parameters = [
            ...$this->modelOptions,
            'model' => $this->model,
            'file' => fopen($fileName, 'r'),
            'response_format' => 'verbose_json',
        ];
        if ($prompt) {
            $parameters['prompt'] = $prompt;
        }
        $response = $this->client->audio()->translate($parameters);

        return $response->text;
    }
}
