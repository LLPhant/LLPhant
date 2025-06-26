<?php

namespace LLPhant\Audio;

use Exception;
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

            $this->client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
                ->withBaseUri($config->url ?? (getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1'))
                ->make();
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
        $response = $this->client->audio()->translate([
            ...$this->modelOptions,
            'model' => $this->model,
            'file' => fopen($fileName, 'r'),
            'response_format' => 'verbose_json',
            'prompt' => $prompt,
        ]);

        return $response->text;
    }
}
