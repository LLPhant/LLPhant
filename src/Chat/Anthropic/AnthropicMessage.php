<?php

namespace LLPhant\Chat\Anthropic;

use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Chat\Message;
use stdClass;

class AnthropicMessage extends Message implements \JsonSerializable
{
    /**
     * @var array<string|int, mixed>
     */
    public array $contentsArray = [];

    /**
     * @param  array<string, mixed>  $toolsOutput
     */
    public static function toolResultMessage(array $toolsOutput): AnthropicMessage
    {
        $message = new self();
        $message->role = ChatRole::User;

        foreach ($toolsOutput as $key => $value) {
            $message->contentsArray[] = [
                'type' => 'tool_result',
                'tool_use_id' => $key,
                'content' => $value,
            ];
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $responses
     */
    public static function fromAssistantAnswer(array $responses): AnthropicMessage
    {
        $message = new self();
        $message->role = ChatRole::Assistant;

        $message->contentsArray = $responses;

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $contents = $this->contentsArray;
        foreach ($contents as &$item) {
            if (($item['type'] ?? null) === 'tool_use' && ($item['input'] ?? null) === []) {
                $item['input'] = new stdClass();
            }
        }
        unset($item);

        return [
            'role' => $this->role->value,
            'content' => $contents,
        ];
    }
}
