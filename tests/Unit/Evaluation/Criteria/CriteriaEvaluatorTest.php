<?php

namespace Tests\Unit\Evaluation\Criteria;

use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Evaluation\Criteria\CriteriaEvaluator;
use LLPhant\Evaluation\Criteria\CriteriaEvaluatorPromptBuilder;
use LLPhant\OpenAIConfig;

it('throws LogicException when wrong param is passed', function (): void {
    $question = 'Does “Ruby on Rails”, the web framework, have anything to do with Ruby Rails, the country singer?';

    $answer = <<<'TEXT'
No — they are completely unrelated.

• **Ruby on Rails** (often just “Rails”) is an open-source web-application framework written in the Ruby programming language.
• **Ruby Rails** (born Ruby Jane Smith, 1999) is an American country & bluegrass fiddler and singer-songwriter.

Aside from sharing the word “Ruby”, the software project and the musician work in entirely different domains.
TEXT;
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();
    $evaluator->setChat(getChatMock());
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);
    $evaluator->evaluateText('some text', '');
})->throws(\LogicException::class);

it('evaluates criteria for text', function (): void {
    $question = 'Does “Ruby on Rails”, the web framework, have anything to do with Ruby Rails, the country singer?';

    $answer = <<<'TEXT'
No — they are completely unrelated.

• **Ruby on Rails** (often just “Rails”) is an open-source web-application framework written in the Ruby programming language.
• **Ruby Rails** (born Ruby Jane Smith, 1999) is an American country & bluegrass fiddler and singer-songwriter.

Aside from sharing the word “Ruby”, the software project and the musician work in entirely different domains.
TEXT;
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();
    $evaluator->setChat(getChatMock());
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);
    $results = $evaluator->evaluateText('some response', 'some question');
    expect($results->getResults())->toBe([
        'correctness' => 5,
        'helpfulness' => 4,
        'relevance' => 4,
        'conciseness' => 5,
        'clarity' => 4,
        'factual_accuracy' => 4,
        'insensitivity' => 5,
        'maliciousness' => 0,
        'harmfulness' => 0,
        'coherence' => 1,
        'misogyny' => 0,
        'criminality' => 0,
        'controversiality' => 0,
        'creativity' => 1,
    ]);
});

it('evaluates criteria for messages', function (): void {
    $question = 'Does “Ruby on Rails”, the web framework, have anything to do with Ruby Rails, the country singer?';

    $answer = <<<'TEXT'
No — they are completely unrelated.

• **Ruby on Rails** (often just “Rails”) is an open-source web-application framework written in the Ruby programming language.
• **Ruby Rails** (born Ruby Jane Smith, 1999) is an American country & bluegrass fiddler and singer-songwriter.

Aside from sharing the word “Ruby”, the software project and the musician work in entirely different domains.
TEXT;
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();
    $evaluator->setChat(getChatMock());
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);
    $results = $evaluator->evaluateMessages([Message::user('some text'), Message::assistant('some question')]);
    expect($results->getResults())->toBe([
        'correctness' => 5,
        'helpfulness' => 4,
        'relevance' => 4,
        'conciseness' => 5,
        'clarity' => 4,
        'factual_accuracy' => 4,
        'insensitivity' => 5,
        'maliciousness' => 0,
        'harmfulness' => 0,
        'coherence' => 1,
        'misogyny' => 0,
        'criminality' => 0,
        'controversiality' => 0,
        'creativity' => 1,
    ]);
});

function getChatMock(): OpenAIChat
{
    return new class extends OpenAIChat
    {
        public function __construct()
        {
            $config = new OpenAIConfig();
            $config->apiKey = 'someKey';
            parent::__construct($config);
        }

        public function generateText(string $prompt): string
        {
            return '{
                "correctness": 5,
                "helpfulness": 4,
                "relevance": 4,
                "conciseness": 5,
                "clarity": 4,
                "factual_accuracy": 4,
                "insensitivity": 5,
                "maliciousness": 0,
                "harmfulness": 0,
                "coherence": 1,
                "misogyny": 0,
                "criminality": 0,
                "controversiality": 0,
                "creativity": 1
            }';
        }
    };
}
