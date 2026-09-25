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

    public function __construct(
        JevConfig                        $config = new JevConfig(),
        private readonly LoggerInterface $logger = new NullLogger())
    {
        $this->url = $config->url;
        $this->model = $config->model;
        $this->apiKey = $config->apiKey
            ?? (string)Utility::readEnvironment('JEV_API_KEY');
        $this->client = $config->client ?: Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory(
            requestFactory: $config->requestFactory,
            streamFactory: $config->streamFactory,
        );
    }

    /**
     * @param string $state
     * @param array<string, QuestionType> $questions
     * @return array <string, Answer>
     * @throws \Exception
     * @throws ClientExceptionInterface
     */
    function askQuestions(string $state, array $questions): array
    {
        $response = $this->sendRequest($state, $questions);

        $contents = $response->getBody()->getContents();

        $jsonContents = Utility::decodeJson($contents);

        $answers = $jsonContents['answers'];

        $result = [];

        foreach ($answers as $key => $value) {
            $result[$key] = $this->decodeAnswer($value);
        }

        return $result;
    }

    /**
     * @@param array<string, QuestionType> $questions
     * @return ResponseInterface
     * @throws HttpException
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    protected function sendRequest(string $state, array $questions): ResponseInterface
    {
        $this->logger->debug('Calling POST v1/systemone', [
            'chat' => self::class,
            'params' => $questions,
        ]);


        $request = $this->factory->createRequest('POST', $this->url);
        $request = $request->withAddedHeader('Content-Type', 'application/json');
        $request = $request->withAddedHeader('Authorization', 'Bearer ' . $this->apiKey);

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
     * @param array<string, mixed> $value
     * @return Answer
     * @throws \Exception
     */
    private function decodeAnswer(array $value): Answer
    {
        $type = $value['type'];
        return match ($type) {
            'noul' => new NoulAnswer($value['noul']),
            'choice' => new ChoiceAnswer(
                choice: $value['choice'],
                probabilities: $value['probabilities'],
                confidence: $value['confidence'],
            ),
            'score' => new ScoreAnswer(
                score: $value['score'],
                legend: $value['legend'],
                probabilities: $value['probabilities'],
                confidence: $value['confidence'],
            ),
            default => throw new \Exception('unexpected answer type: ' . $type),
        };
    }
}
