<?php

namespace LLPhant\Evaluation;

class GPTCriteriaEvaluator extends AbstractGPTAPIClient
{
    private const MODEL = 'o3';

    public function evaluate(string $evaluationPrompt): string
    {
        // prepare API input
        $input = "\n\n##### INPUT: \n".$evaluationPrompt."\n##### RESPONSE:\n";

        // get API response
        $response = $this->client->chat()->create([
            'model' => self::MODEL,
            'messages' => [
                [
                    'content' => $input,
                    'role' => 'user',
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }
}
