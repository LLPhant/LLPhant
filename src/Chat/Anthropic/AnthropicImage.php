<?php

namespace LLPhant\Chat\Anthropic;

class AnthropicImage
{
    public function __construct(private readonly AnthropicImageType $type, private readonly string $base64)
    {
        if (! $this->isBase64($base64)) {
            throw new \InvalidArgumentException('Invalid base64');
        }
    }

    protected function isBase64(string $image): bool
    {
        return \preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $image) === 1;
    }

    public function getMediaType(): string
    {
        return $this->type->value;
    }

    public function getData(): string
    {
        return $this->base64;
    }
}
