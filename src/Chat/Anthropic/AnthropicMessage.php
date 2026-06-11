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

        // A tool_use block for a parameterless tool decodes its `input: {}` as an empty
        // PHP array, which would re-serialise as `[]` and be rejected by Anthropic
        // (400 "tool_use.input: Input should be an object"). Coerce it back to an object.
        foreach ($responses as &$response) {
            if (($response['type'] ?? null) === 'tool_use' && ($response['input'] ?? null) === []) {
                $response['input'] = new stdClass();
            }
        }
        unset($response);

        $message->contentsArray = $responses;

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->contentsArray,
        ];
    }
}
