<?php

namespace LLPhant\Tool;

use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Render\CLIOutputUtils;
use LLPhant\Render\OutputAgentInterface;
use Psr\Http\Client\ClientExceptionInterface;

class ApiRequest extends ToolBase
{
    /**
     * @throws Exception
     */
    public function __construct(
        bool $verbose = false,
        public OutputAgentInterface $outputAgent = new CLIOutputUtils(),
    ) {
        parent::__construct($verbose);
    }

    /**
     * Perform a http get call to the provided url
     */
    public function get_data_from_url(string $url): string
    {
        try {
            $this->outputAgent->renderTitleAndMessageOrange('🔧 Executing tool ApiRequest', $url, $this->verbose);
            $client = Psr18ClientDiscovery::find();
            $response = $client->sendRequest((new Psr17Factory())->createRequest('GET', $url));

            $rawContent = $response->getBody()->getContents();
            $this->outputAgent->render("Results from ApiRequest to {$url}: {$rawContent}", $this->verbose);
            $this->wasSuccessful = true;

        } catch (ClientExceptionInterface $e) {
            $this->wasSuccessful = false;
            $this->lastResponse = $e->getMessage();
        }

        return $rawContent ?? '';
    }
}
