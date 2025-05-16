<?php

namespace LLPhant\Evaluation;

class ClaudeCriteriaEvaluator extends AbstractClaudeAPIClient
{
    private const MODEL = 'claude-3-5-sonnet-20241022';

    public function evaluate(string $evaluationPrompt): string
    {
        $response = $this->request(
            $evaluationPrompt,
            'messages',
            self::MODEL
        );

        return $response['content'][0]['text'];
    }
}
