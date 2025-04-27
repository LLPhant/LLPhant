<?php

declare(strict_types=1);

namespace Tests\Integration\Query\SemanticSearch;

use LLPhant\Chat\Enums\OpenAIChatModel;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\ChatSession;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Tests\Integration\Chat\WeatherExample;

it('can call a function and provide the result to the assistant', function () {
    $config = new OpenAIConfig();
    //Functions work only with older models. Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt35Turbo->value;
    $chat = new OpenAIChat($config);
    $location = new Parameter('location', 'string', 'the name of the city, the state or province and the nation');
    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addTool($function);

    $qa = new QuestionAnswering(
        new MemoryVectorStore(),
        new OpenAI3SmallEmbeddingGenerator(),
        $chat
    );

    $answer = $qa->answerQuestionFromChat(messages: [Message::user('What is the weather in Venice?')], stream: false);

    expect($answer)->toContain('sunny');
});

it('can remember chat history using answerQuestion method', function () {
    $config = new OpenAIConfig();
    //Functions work only with older models. Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt4Omni->value;
    $chat = new OpenAIChat($config);

    $qa = new QuestionAnswering(
        new MemoryVectorStore(),
        new OpenAI3SmallEmbeddingGenerator(),
        $chat,
        session: new ChatSession()
    );

    $answer = $qa->answerQuestion('What is the name of the first official Roman Emperor?');
    expect($answer)->toContain('Augustus');

    $answer = $qa->answerQuestion('And who was the third one?');
    expect($answer)->toContain('Caligula');

    $answer = $qa->answerQuestion('Who was his successor?');
    expect($answer)->toContain('Claudius');
});

it('can remember chat history using answerQuestionStream method', function () {
    $config = new OpenAIConfig();
    //Functions work only with older models. Tools are needed with newer models
    $config->model = OpenAIChatModel::Gpt4Omni->value;
    $chat = new OpenAIChat($config);

    $qa = new QuestionAnswering(
        new MemoryVectorStore(),
        new OpenAI3SmallEmbeddingGenerator(),
        $chat,
        session: new ChatSession()
    );

    $answer = $qa->answerQuestionStream('What is the name of the first official Roman Emperor?')->getContents();
    expect($answer)->toContain('Augustus');

    $answer = $qa->answerQuestionStream('And who was the third one?')->getContents();
    expect($answer)->toContain('Caligula');

    $answer = $qa->answerQuestionStream('Who was his successor?')->getContents();
    expect($answer)->toContain('Claudius');
});
