<?php

declare(strict_types=1);

namespace LLPhant\Classification;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use JsonException;
use LLPhant\Exception\HttpException;
use LLPhant\Utility;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JevClassifier implements ClassifierInterface
{
    private string $model;

    private string $apiKey;

    private ClientInterface $client;

    private Psr17Factory $factory;

    private string $url;

    public function __construct(
        JevConfig $config = new JevConfig(),
        private readonly LoggerInterface $logger = new NullLogger())
    {
        $this->url = $config->url;
        $this->model = $config->model;
        $this->apiKey = $config->apiKey
            ?? (string) Utility::readEnvironment('JEV_API_KEY');
        $this->client = $config->client ?: Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory(
            requestFactory: $config->requestFactory,
            streamFactory: $config->streamFactory,
        );
    }

    /**
     * @param  array<string, QuestionType>  $questions
     * @return array <string, Answer>
     *
     * @throws \Exception
     * @throws ClientExceptionInterface
     */
    public function askQuestions(string $state, array $questions): array
    {
        $response = $this->sendRequest($state, $questions);

        $contents = $response->getBody()->getContents();

        $jsonContents = Utility::decodeJson($contents);

        $answers = $jsonContents['answers'];
        $usage = $jsonContents['usage'] ?? [];
        $inputTokens = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? 0);

        $result = [];

        foreach ($answers as $key => $value) {
            $result[$key] = $this->decodeAnswer($value, $inputTokens, $outputTokens);
        }

        return $result;
    }

    /**
     * @param  array<string, QuestionType>  $questions
     *
     * @throws ClientExceptionInterface
     * @throws HttpException
     * @throws JsonException
     */
    protected function sendRequest(string $state, array $questions): ResponseInterface
    {
        $this->logger->debug('Calling POST v1/systemone', [
            'chat' => self::class,
            'params' => $questions,
        ]);

        $request = $this->factory->createRequest('POST', $this->url);
        $request = $request->withAddedHeader('Content-Type', 'application/json');
        $request = $request->withAddedHeader('Authorization', 'Bearer '.$this->apiKey);

        $data = new JevRequestBody($this->model, $state, $questions);

        $request = $request->withBody($this->factory->createStream($data->toJSON()));

        $response = $this->client->sendRequest($request);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new HttpException(sprintf(
                'HTTP error Jev (%s): %s',
                $status,
                $response->getBody()->getContents(),
            ));
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $value
     *
     * @throws \Exception
     */
    private function decodeAnswer(array $value, int $inputTokens, int $outputTokens): Answer
    {
        $type = $value['type'];

        return match ($type) {
            'noul' => new NoulAnswer(
                $value['noul'],
                $inputTokens,
                $outputTokens,
            ),
            'choice' => new ChoiceAnswer(
                choice: $value['choice'],
                probabilities: $value['probabilities'],
                confidence: $value['confidence'],
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
            ),
            'score' => new ScoreAnswer(
                score: $value['score'],
                legend: $value['legend'],
                probabilities: $value['probabilities'],
                confidence: $value['confidence'],
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
            ),
            default => throw new \Exception('unexpected answer type: '.$type),
        };
    }
}
