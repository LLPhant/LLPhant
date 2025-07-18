<?php

declare(strict_types=1);

namespace Tests\Integration\Tool;

use LLPhant\Render\OutputAgentInterface;
use LLPhant\Tool\ApiRequest;
use Mockery;

it('can perform API requests', function () {
    $spyOutputAgent = Mockery::mock(OutputAgentInterface::class);
    $spyOutputAgent->shouldReceive('renderTitleAndMessageOrange');
    $spyOutputAgent->shouldReceive('render');
    $apiRequest = new ApiRequest(verbose: false, outputAgent: $spyOutputAgent);
    $apiRequest->get_data_from_url('https://httpbin.org/get');
    $spyOutputAgent->shouldHaveReceived('render');
    expect($apiRequest->wasSuccessful)->toBeTrue();
});
