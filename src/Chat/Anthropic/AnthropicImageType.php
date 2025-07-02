<?php

namespace LLPhant\Chat\Anthropic;

enum AnthropicImageType: string
{
    case PNG = 'image/png';
    case JPEG = 'image/jpeg';
    case GIF = 'image/gif';
    case WEBP = 'image/webp ';
}
