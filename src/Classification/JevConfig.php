<?php

declare(strict_types=1);

namespace LLPhant\Classification;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class JevConfig
{
    const URL = 'https://api.typesafe.ai/v1/systemone';

    const LATEST = 'jev-latest';

    public function __construct(
        public readonly ?string $apiKey = null,
        public readonly string $url = self::URL,
        public readonly string $model = self::LATEST,
        public readonly ?ClientInterface $client = null,
        public readonly ?RequestFactoryInterface $requestFactory = null,
        public readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
    }
}
