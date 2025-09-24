<?php

namespace LLPhant\Chat;

use GuzzleHttp\Middleware;
use LLPhant\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;

class OpenAIResponseErrorsProcessor
{
    public static function createResponseModifier(): callable
    {
        return Middleware::mapResponse(fn: self::getModifierFunction());
    }

    private static function getModifierFunction(): \Closure
    {
        return function (ResponseInterface $response): ResponseInterface {
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw new HttpException(
                    message: sprintf(
                        'HTTP error from AI engine (%d): %s',
                        $status,
                        $response->getBody()->getContents()
                    ),
                    code: $status
                );
            }

            return $response;
        };
    }
}
