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
            ?? (string)Utility::readEnvironment('ANTHROPIC_API_KEY');
        $this->client = $config->client ?: Psr18ClientDiscovery::find();
        $this->factory = new Psr17Factory(
            requestFactory: $config->requestFactory,
            streamFactory: $config->streamFactory,
        );
    }

    /**
     * @param string $state
     * @param array<string, QuestionType> $questions
     * @return array <int, NoulAnswer>
     */
    function noul(string $state, array $questions): array
    {
        // TODO: Implement noul() method.
    }

    /**
     * @param string $state
     * @param array<string, ChoiceType> $questions
     * @return array <int, ChoiceAnswer>
     */
    function choice(string $state, array $questions): array
    {
        // TODO: Implement choice() method.
    }

    /**
     * @param string $state
     * @param array<string, ScoreType> $questions
     * @return array <int, ScoreAnswer>
     */
    function score(string $state, array $questions): array
    {
        // TODO: Implement score() method.
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
        $this->logger->debug('Calling POST v1/messages', [
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
}
