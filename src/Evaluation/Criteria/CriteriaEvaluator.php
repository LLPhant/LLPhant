<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\Criteria;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Message;
use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;

final class CriteriaEvaluator extends AbstractEvaluator
{
    private ChatInterface $chat;

    private CriteriaEvaluatorPromptBuilder $criteriaPromptBuilder;

    public function setChat(ChatInterface $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function setCriteriaPromptBuilder(CriteriaEvaluatorPromptBuilder $criteriaPromptBuilder): self
    {
        $this->criteriaPromptBuilder = $criteriaPromptBuilder;

        return $this;
    }

    /**
     * @throws \JsonException
     * @throws \LogicException
     */
    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        if ($n !== 1) {
            throw new \LogicException("Criteria evaluator doesn't support N-grams. Keep default param value.");
        }
        if ($reference === '') {
            throw new \LogicException('Pass user message as reference');
        }
        $evaluationPrompt = $this->criteriaPromptBuilder->getEvaluationPromptForQuestion($reference, $candidate);
        $this->chat->setSystemMessage($evaluationPrompt);
        $criteriaJSON = $this->chat->generateText(
            'Score the answer from 1-5 for each criterion and return valid JSON only.'
        );

        return new EvaluationResults(
            'Criteria Evaluation Results',
            json_decode($criteriaJSON, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param  Message[]  $messages
     * @param  string[]  $references  should be empty for CriteriaEvaluator
     * @param  int  $n  not supported for criteria evaluator
     *
     * @throws \JsonException
     * @throws \LogicException
     */
    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        if ($n !== 1) {
            throw new \LogicException("Criteria evaluator doesn't support N-grams. Keep default param value.");
        }
        if ($references === []) {
            throw new \LogicException('Pass user messages as reference');
        }

        $assistantMessages = array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'assistant' ? $message->content : null,
            $messages,
        ));

        $userMessages = array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'user' ? $message->content : null,
            $messages,
        ));

        $evaluationPrompt = $this->criteriaPromptBuilder->getEvaluationPromptForConversation($userMessages, $assistantMessages);
        $this->chat->setSystemMessage($evaluationPrompt);
        $criteriaJSON = $this->chat->generateText(
            'Score the answers from 1-5 for each criterion and return valid JSON only.'
        );

        return new EvaluationResults(
            'Criteria Evaluation Results',
            json_decode($criteriaJSON, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
