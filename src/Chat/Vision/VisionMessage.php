<?php

namespace LLPhant\Chat\Vision;

use JsonSerializable;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\Message;

class VisionMessage extends Message implements JsonSerializable
{
    /** @var ImageSource[] */
    public array $images = [];

    /**
     * @param  ImageSource[]  $images
     */
    public static function fromImages(array $images, ?string $message = null): self
    {
        $instance = new self;
        $instance->role = ChatRole::User;
        if ($message === null) {
            $instance->content = 'Describe the image'.(\count($images) > 1 ? 's' : '').'; Output must contain no other URL than the input image url';
        } else {
            $instance->content = $message;
        }
        $instance->images = $images;

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'role' => $this->role->value,
            'content' => array_merge(
                [['type' => 'text', 'text' => $this->content]],
                $this->images
            ),
        ];
    }
}
