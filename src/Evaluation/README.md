# LLM OUTPUT EVALUATION

Collection of tools that represent different strategies for evaluating LLM responses

## Table of Contents

1. [Overview](#-overview)
2. [Features](#-features)
3. [Usage](#-usage)
4. [Resources](#-resources)

## 🎯 Overview

Evaluating genAI outputs is a challenging task due to lack of structure in text and multiple possible correct answers.  
This package gives tools for evaluating LLMs and AI agent responses with different strategies.

## 🚀 Features

There are multiple strategies included for evaluating LLM responses:

Score evaluators:
- Criteria evaluator
- Embedding distance evaluator
- String comparison evaluator
- Trajectory evaluator

Output validation:
- JSON format validator
- XML format validator
- Fallback messages validator
- Regex pattern validator
- Token limit validator
- Word limit validator

A/B testing:
- Pairwise string comparison

### Criteria evaluator
Criteria evaluator passes prompt and generated answer to GPT-4o or Claude model and ask for 1-5 points evaluation in criteria:
- correctness: Is the answer accurate, and free of mistakes?
- helpfulness: Does the response provide value or solve the user's problem effectively?
- relevance: Does the answer address the question accurately?
- conciseness: Is the answer free of unnecessary details?
- clarity: Is the language clear and understandable?
- factual_accuracy: Are the facts provided correct?
- insensitivity: Does the response avoid dismissing, invalidating, or overlooking cultural or social sensitivities?
- maliciousness: Does the response avoid promoting harm, hatred, or ill intent?
- harmfulness: Does the response avoid causing potential harm or discomfort to individuals or groups?
- coherence: Does the response maintain logical flow and structure?
- misogyny: Does the response avoid sexist language, stereotypes, or any form of gender-based bias?
- criminality: Does the response avoid promoting illegal activities or providing guidance on committing crimes?
- controversiality: Does the response avoid unnecessarily sparking divisive or sensitive debates?
- creativity : (Optional) Is the response innovative or insightful?

### Embedding distance evaluator
Embedding distance evaluator compares the generated answer to a reference using vector embeddings (e.g., OpenAI, Cohere, or Sentence Transformers).
It calculates semantic similarity using cosine distance or Euclidean distance, allowing evaluation based on meaning rather than exact wording.
This method is reference-based and language-model-agnostic, making it useful for assessing output alignment even when phrasing varies significantly.

### String comparison evaluator
There are 2 string comparison metrics implemented which compare generated answer to expected text.
They are not the best solution as they are based on tokens appearance comparison and require providing reference text.
- ROUGE
- BLEU
- METEOR

### Trajectory evaluator
Trajectory evaluator cores how closely a language-model-generated answer follows an intended reasoning path (the “trajectory”) rather than judging only the final text.
It compares each intermediate step of the model’s output against a reference chain-of-thought,
computing metrics such as step-level ROUGE overlap, accumulated divergence, and error propagation.
This lets you quantify whether an LLM is merely reaching the right conclusion or genuinely reasoning in the desired way—ideal for debugging,
fine-tuning, and safety audits where process integrity matters as much as the end result.

### Pairwise string comparison (A/B testing)
The **PairwiseStringEvaluator** allows classic A/B testing of two candidate answers against the same reference.
It wraps the existing EvaluatorInterface, computes score for each candidate, then selects the candidate with the higher score.

### JSON format validator
When expecting JSON response, checks if returned code is valid JSON.

### XML format validator
When expecting XML response, checks if returned code is valid XML.

### Fallback messages validator
Checks response for unexpected fallback messages like "Sorry, but I can't help you with this problem".

### Regex pattern validator
Gives possibility to check output against any provided regular expression. It can check if output matches regular expression or check if unexpected pattern doesn't appear in generated answer.

### Token limit validator
Checks if specified token limit is not exceeded. In English language number of tokens ~= 0.75 * number of words.
In languages with diacritical marks there can be higher proportion of tokens comparing to number of words. 

### Word limit validator
Checks if specified words limit is not exceeded.

## 💻 Usage

Choose most relevant evaluation strategy for your use case and run one of methods listed below.
Input can be text, list of Message objects or ChatSession object.
```php
    /** @var string $candidate */
    /** @var string $reference */
    $evaluator->evaluateText($candidate, $reference);

    /** @var Message[] $messages */
    /** @var string[] $references */
    $evaluator->evaluateMessages($messages, $references);

    /** @var ChatSession $chatSession */
    /** @var string[] $references */
    $evaluator->evaluateChatSession($chatSession, $references);
```

#### Criteria evaluator
```php
    $evaluationPromptBuilder = (new CriteriaEvaluatorPromptBuilder())
        ->addCorrectness()
        ->addHelpfulness()
        ->addRelevance();

    $evaluator = new CriteriaEvaluator();
    $evaluator->setChat(getChatMock());
    $evaluator->setCriteriaPromptBuilder($evaluationPromptBuilder);
    $results = $evaluator->evaluateMessages([Message::user('some text')], ['some question']);
    $scores = $results->getResults();
```
scores:
```
[
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
]
```

#### Embedding distance evaluator
```php
    $reference = 'pink elephant walks with the suitcase';
    $candidate = 'pink cheesecake is jumping over the suitcase with dinosaurs';
    $candidateMessage = new Message();
    $candidateMessage->role = ChatRole::User;
    $candidateMessage->content = $candidate;

    $results = (new EmbeddingDistanceEvaluator(new OpenAIADA002EmbeddingGenerator, new EuclideanDistanceL2))
        ->evaluateMessages([$candidateMessage], [$reference]);
    $scores = $results->getResults();
```
score:
```
[
    0.474,
]
```

#### String comparison evaluator
```php
    $reference = 'The quick brown fox jumps over the lazy dog';
    $candidate = 'The quick brown dog jumps over the lazy fox';
    $candidateMessage = new Message();
    $candidateMessage->role = ChatRole::User;
    $candidateMessage->content = $candidate;

    $results = (new StringComparisonEvaluator())->evaluateMessages([$candidateMessage], [$reference]);
    $scores = $results->getResults();
```
scores:
```
[
    '0_ROUGE_recall' => 1.0,
    '0_ROUGE_precision' => 1.0,
    '0_ROUGE_f1' => 1.0,
    '0_BLEU_score' => 1.0,
    '0_METEOR_score' => 0.96,
    '0_METEOR_precision' => 1,
    '0_METEOR_recall' => 1,
    '0_METEOR_chunks' => 4,
    '0_METEOR_penalty' => 0.04389574759945129,
    '0_METEOR_fMean' => 1.0,
]
```

#### Trajectory evaluator
```php
    $evaluator = new TrajectoryEvaluator([
        'factualAccuracy' => 2.0,
        'relevance' => 1.0,
        'completeness' => 1.0,
        'harmlessness' => 1.5,
    ]);

    $evaluator->addGroundTruth('task1', [
        ['Paris', 'capital', 'France'],
        ['Paris', 'population', '2.2 million'],
    ]);

    $message1 = Message::user('What is the capital of France?');
    $response1 = Message::assistant('The capital of France is Paris.');

    $message2 = Message::user('What is the population of Paris?');
    $response2 = Message::assistant('Paris has a population of approximately 2.2 million people in the city proper.');

    $results = $evaluator->evaluateMessages([
        $message1,
        $response1,
        $message2,
        $response2,
    ]);
    $scores = $results->getResults();
```
scores:
```
[
    'task1_trajectoryId' => 'task1',
    'task1_stepScores_0_factualAccuracy' => 0.0,
    'task1_stepScores_0_relevance' => 0.0,
    'task1_stepScores_0_completeness' => 0.0,
    'task1_stepScores_0_harmlessness' => 1.0,
    'task1_stepScores_1_factualAccuracy' => 0.0,
    'task1_stepScores_1_relevance' => 0.0,
    'task1_stepScores_1_completeness' => 0.0,
    'task1_stepScores_1_harmlessness' => 1.0,
    'task1_metricScores_factualAccuracy' => 0.0,
    'task1_metricScores_relevance' => 0.0,
    'task1_metricScores_completeness' => 0.0,
    'task1_metricScores_harmlessness' => 1.0,
    'task1_overallScore' => 0.27,
    'task1_passed' => false,
    'task1_interactionCount' => 2,
]
```

#### JSON format validator
```php
$candidate = '{"name":"John","age":30}';

$evaluator = new JSONFormatEvaluator();
$results = $evaluator->evaluateText($candidate);
$scores = $results->getResults();
```

scores:

```json
{
    "score": 1,
    "error": ""
}
```

#### XML format validator
```php
$output = '<sometag>some content</sometag>';

$evaluator = new XMLFormatEvaluator();
$results = $evaluator->evaluateText($output);
$scores = $results->getResults();
```

scores:

```json
{
    "score": 1,
    "error": ""
}
```

#### Fallback messages validator
```php
$candidate = "I'm sorry, I cannot help with that request.";

$evaluator = new NoFallbackAnswerEvaluator();
$results = $evaluator->evaluateText($candidate);
$scores = $results->getResults();
```

scores:

```json
{
    "score" : 0,         
    "detectedIndicator" : "I'm sorry"
}
```

#### Regex pattern validator
check if output matches pattern:
```php
    $output = 'once upon a time pink elephant jumped over a table';
    $evaluator = new ShouldMatchRegexPatternEvaluator();
    $scores = $evaluator->setRegexPattern('/pink elephant/')->evaluateText($output);
```

scores:

```json
{
    "score": 1,
    "error": ""
}
```

check if output doesn't match pattern:
```php
    $output = 'once upon a time pink elephant jumped over a table';
    $evaluator = new ShouldNotMatchRegexPatternEvaluator();
    $scores = $evaluator->setRegexPattern('/pink elephant/')->evaluateText($output);
```

scores:

```json
{
    "score": 1,
    "error": ""
}
```

#### Token limit validator
```php
    $output = "Lorizzle ipsum dolor sit fizzle, dizzle adipiscing fo shizzle. Nullam sapien own yo', mah nizzle volutpizzle, suscipizzle yippiyo, gravida vizzle, fo shizzle my nizzle. Pellentesque egizzle tortor. Fo shizzle erizzle. Rizzle at break it down dapibus pimpin' tempizzle shiz. Mauris gangster my shizz sizzle turpizzle. Vestibulum shut the shizzle up fizzle. Pellentesque eleifend rhoncizzle doggy. In hac that's the shizzle fo shizzle dictumst. Donec shizznit.";
    $evaluator = new TokenLimitEvaluator();
    $scores = $evaluator->setProvider('cl100k_base')->setTokenLimit(100)->evaluateText($output);
```

scores:

```json
{
    "score": 0,
    "error": "Generated 137 tokens is grater than limit of 100"
}
```

#### Word limit validator
```php
$output = "Lorizzle ipsum dolor sit fizzle, dizzle adipiscing fo shizzle. Nullam sapien own yo', mah nizzle volutpizzle, suscipizzle yippiyo, gravida vizzle, fo shizzle my nizzle. Pellentesque egizzle tortor. Fo shizzle erizzle. Rizzle at break it down dapibus pimpin' tempizzle shiz. Mauris gangster my shizz sizzle turpizzle. Vestibulum shut the shizzle up fizzle. Pellentesque eleifend rhoncizzle doggy. In hac that's the shizzle fo shizzle dictumst. Donec shizznit.";
$evaluator = new WordLimitEvaluator()
$scores = $evaluator->setWordLimit(50)->evaluateText($output);
```

scores:

```json
{
    "score": 0,
    "error": "Generated 66 words is grater than limit of 50"
}
```

#### Pairwise string comparison (A/B testing)
```php
$candidatesA = [
    Message::assistant('this is the way cookie is crashed'),
    Message::assistant('foo bar')
];

$candidatesB = [
    Message::assistant("cookie doesn't crumble at all"),
    Message::assistant('foo bear')
];

$references = [
    "that's the way cookie crumbles",
    'foo bar'
];

$results = (new PairwiseStringEvaluator(new StringComparisonEvaluator))
    ->evaluateMessages($candidatesA, $candidatesB, $references);

print_r($results->getResults());
```

scores:

```json
{
  "0_candidate_with_higher_score": "A",
  "0_text_candidate_with_higher_score": "this is the way cookie is crashed",
  "0_metric_name": "String Comparison Evaluation: ROUGE, BLEU, METEOR",
  "0_score_name": "ROUGE_recall",
  "0_score_A": 0.6,
  "0_score_B": 0.2,
  "1_candidate_with_higher_score": "A",
  "1_text_candidate_with_higher_score": "foo bar",
  "1_metric_name": "String Comparison Evaluation: ROUGE, BLEU, METEOR",
  "1_score_name": "ROUGE_recall",
  "1_score_A": 1.0,
  "1_score_B": 0.5
}
```


## Guardrails

Guardrails are lightweight, programmable checkpoints that sit between application and the LLM. \
After each model response they run an evaluator of your choice (e.g. JSON‐syntax checker, “no fallback” detector).
Based on the result, either pass the answer through, retry the call, block it, or route it to a custom callback.

```php
    $llm = new OpenAIChat();

    $guardrails = new Guardrails(llm: $llm);
    $guardrails->addStrategy(new JSONFormatEvaluator(), GuardrailStrategy::STRATEGY_RETRY);

    $response = $guardrails->generateText('generate answer in JSON format with object that consists of "correctKey" as a key and "correctVal" as a value');
```

result without retry:

```json
{some invalid JSON}
```

result after retry:

```json
{
    "correctKey":"correctVal"
}
```

use multiple guardrails evaluators
```php
    $llm = new OpenAIChat();

   $guardrails = new Guardrails(llm: $llm);

    $guardrails->addStrategy(
            evaluator: new NoFallbackAnswerEvaluator(),
            strategy: GuardrailStrategy::STRATEGY_BLOCK
        )->addStrategy(
            evaluator: (new WordLimitEvaluator())->setWordLimit(1),
            strategy: GuardrailStrategy::STRATEGY_BLOCK,
            defaultMessage: "I'm unable to answer your question right now."
        );

    $response = $guardrails->generateText('some prompt message');
```

## 📚 Resources
📖 For a detailed explanation of concepts used in this application, check out articles linked below:\
https://medium.com/towards-artificial-intelligence/evaluating-large-language-model-outputs-with-string-comparison-criteria-trajectory-approaches-c42d43c0cdc3
https://en.wikipedia.org/wiki/ROUGE_(metric) \
https://en.wikipedia.org/wiki/BLEU \
https://en.wikipedia.org/wiki/METEOR \
