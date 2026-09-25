<?php

namespace LLPhant\Chat\Anthropic;

use LLPhant\Chat\Enums\ChatRole;

class AnthropicVisionMessage extends AnthropicMessage
{
    /**
     * @param  array<AnthropicImage>  $anthropicImages
     */
    public function __construct(array $anthropicImages, ?string $message = null)
    {
        $this->role = ChatRole::User;
        foreach ($anthropicImages as $image) {
            $this->contentsArray[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $image->getMediaType(),
                    'data' => $image->getData(),
                ],
            ];
        }
        if ($message === null) {
            $this->content = 'Describe the image'.(\count($anthropicImages) > 1 ? 's' : '');
        } else {
            $this->content = $message;
        }
        $this->contentsArray[] = [
            'type' => 'text',
            'text' => $this->content,
        ];
    }
}
