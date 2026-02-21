<?php

namespace LLPhant\Image;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use LLPhant\Image\Enums\OpenAIImageStyle;

/**
 * ModelsLab Image generation client.
 *
 * Generates images using the ModelsLab REST API, supporting Flux,
 * SDXL, Stable Diffusion, and 10,000+ community fine-tuned models.
 *
 * @see https://docs.modelslab.com/image-generation/overview
 *
 * Usage:
 *
 *     $image = new ModelsLabImage();  // reads MODELSLAB_API_KEY env var
 *     $result = $image->generateImage('A sunset over mountains');
 *     echo $result->url;
 *
 * Or inject the API key directly:
 *
 *     $image = new ModelsLabImage(apiKey: 'your-api-key');
 *     $image->model = 'sdxl';  // default: 'flux'
 *     $result = $image->generateImage('A cozy cabin in the woods');
 */
class ModelsLabImage implements ImageInterface
{
    private const API_BASE_URL = 'https://modelslab.com/api/v6/';

    private const ENDPOINT_TEXT2IMG = 'images/text2img';

    private readonly GuzzleClient $client;

    public string $model = 'flux';

    public int $width = 1024;

    public int $height = 1024;

    public int $numInferenceSteps = 30;

    public float $guidanceScale = 7.5;

    public ?string $negativePrompt = null;

    public ?int $seed = null;

    private readonly string $apiKey;

    /**
     * @throws Exception
     */
    public function __construct(
        ?string $apiKey = null,
        int $timeout = 120,
    ) {
        $resolvedKey = $apiKey ?? getenv('MODELSLAB_API_KEY');

        if (! $resolvedKey) {
            throw new Exception(
                'You must provide a ModelsLab API key. '
                .'Set the MODELSLAB_API_KEY environment variable or pass it to the constructor.'
            );
        }

        $this->apiKey = $resolvedKey;
        $this->client = new GuzzleClient([
            'base_uri' => self::API_BASE_URL,
            'timeout' => $timeout,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * Generate an image using the ModelsLab API.
     *
     * @param  string  $prompt  Text description of the image to generate
     * @param  OpenAIImageStyle  $style  Accepted for interface compatibility but ignored by ModelsLab
     *
     * @throws Exception
     */
    public function generateImage(string $prompt, OpenAIImageStyle $style = OpenAIImageStyle::Vivid): Image
    {
        $payload = [
            'key' => $this->apiKey,
            'prompt' => $prompt,
            'model_id' => $this->model,
            'width' => (string) $this->width,
            'height' => (string) $this->height,
            'samples' => '1',
            'num_inference_steps' => (string) $this->numInferenceSteps,
            'guidance_scale' => $this->guidanceScale,
            'safety_checker' => 'no',
        ];

        if ($this->negativePrompt !== null) {
            $payload['negative_prompt'] = $this->negativePrompt;
        }

        if ($this->seed !== null) {
            $payload['seed'] = $this->seed;
        }

        $response = $this->client->post(self::ENDPOINT_TEXT2IMG, [
            'json' => $payload,
        ]);

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $this->responseToImage($data, $prompt);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    private function responseToImage(array $data, string $originalPrompt): Image
    {
        $status = $data['status'] ?? 'unknown';

        if ($status === 'error') {
            $message = $data['message'] ?? $data['messege'] ?? 'Unknown ModelsLab error';
            throw new Exception("ModelsLab API error: {$message}");
        }

        if ($status === 'processing') {
            $id = $data['id'] ?? 'unknown';
            throw new Exception(
                "ModelsLab image generation is still processing (id: {$id}). "
                .'The request may take longer than expected. Please retry.'
            );
        }

        if ($status !== 'success') {
            throw new Exception("Unexpected ModelsLab status: {$status}");
        }

        /** @var list<string> $outputs */
        $outputs = $data['output'] ?? [];

        if (empty($outputs)) {
            throw new Exception('ModelsLab returned no image output.');
        }

        $image = new Image();
        $image->url = $outputs[0];
        $image->revisedPrompt = $originalPrompt;

        return $image;
    }
}
