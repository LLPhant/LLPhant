<?php

namespace LLPhant\Chat\Vision;

use GuzzleHttp\Client;
use JsonSerializable;

class ImageSource implements JsonSerializable
{
    private readonly string $url;

    private ?string $base64 = null;

    public function __construct(string $urlOrBase64Image, private readonly ImageQuality $detail = ImageQuality::Auto)
    {
        $this->url = $this->decodeUrl($urlOrBase64Image);
    }

    private function decodeUrl(string $urlOrBase64Image): string
    {
        if ($this->isUrl($urlOrBase64Image)) {
            return $urlOrBase64Image;
        }
        if ($this->isBase64($urlOrBase64Image)) {
            $imageType = $this->imageType($urlOrBase64Image);
            if ($imageType !== null) {
                $this->base64 = $urlOrBase64Image;

                return "data:{$imageType};base64,${urlOrBase64Image}";
            }
        }
        throw new \InvalidArgumentException('Invalid image URL or base64 format.');
    }

    protected function isUrl(string $image): bool
    {
        return \filter_var($image, FILTER_VALIDATE_URL) !== false;
    }

    protected function isBase64(string $image): bool
    {
        return \preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $image) === 1;
    }

    public function getBase64(Client $client): string
    {
        if (is_null($this->base64)) {
            $response = $client->request('GET', $this->url);
            $imageData = (string) $response->getBody();
            $this->base64 = base64_encode($imageData);
        }

        return $this->base64;
    }

    /**
     * @return array{type: string, image_url: array{url: string, detail: string}}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => $this->url,
                'detail' => $this->detail->value,
            ],
        ];
    }

    private function imageType(string $urlOrBase64Image): ?string
    {
        $binaryData = base64_decode($urlOrBase64Image, true);

        if ($binaryData === false) {
            return null;
        }

        $binaryDataHex = strtoupper(bin2hex($binaryData));

        if (\str_starts_with($binaryDataHex, '89504E470D0A1A0A')) {
            return 'image/png';
        }
        if (\str_starts_with($binaryDataHex, '474946383761')) {
            return 'image/gif';
        }
        if (\str_starts_with($binaryDataHex, '474946383961')) {
            return 'image/gif';
        }
        // Check JPEG signature (starts with FF D8 and ends with FF D9)
        if (! \str_starts_with($binaryDataHex, 'FFD8')) {
            return null;
        }
        if (! \str_ends_with($binaryDataHex, 'FFD9')) {
            return null;
        }

        return 'image/jpeg';
    }
}
