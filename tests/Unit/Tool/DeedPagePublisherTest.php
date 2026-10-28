<?php

declare(strict_types=1);

namespace Tests\Unit\Tool;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Tool\DeedPagePublisher;
use Psr\Http\Message\RequestInterface;

/**
 * @param  Response[]  $responses
 * @param  array<int, array{request: RequestInterface}>  $history
 */
function deedPageClient(array $responses, array &$history): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new Client(['handler' => $stack]);
}

beforeEach(function (): void {
    putenv('DEEDPAGE_TOKEN');
});

it('publishes the html as index.html and returns only the url', function (): void {
    $history = [];
    $client = deedPageClient([new Response(200, [], '{"ok":true,"url":"https://quiet-fox-1a2b.deed.page/","slug":"quiet-fox-1a2b","version":"v1","token":"deed_tok_secret"}')], $history);
    $tool = new DeedPagePublisher(client: $client);

    $url = $tool->publishHtml('<h1>Hello</h1>');

    expect($url)->toBe('https://quiet-fox-1a2b.deed.page/')
        ->and($url)->not->toContain('deed_tok_secret')
        ->and($tool->lastResponse)->toBe($url)
        ->and($tool->wasSuccessful)->toBeTrue();

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://deed.page/v1/sites')
        ->and($request->getHeaderLine('X-Client'))->toBe('LLPhant')
        ->and($request->hasHeader('Authorization'))->toBeFalse()
        ->and($request->hasHeader('X-Ttl'))->toBeFalse()
        ->and(json_decode((string) $request->getBody(), true))->toBe(['files' => ['index.html' => '<h1>Hello</h1>']]);
});

it('keeps the token from the first publish and sends it on later calls', function (): void {
    $history = [];
    $client = deedPageClient([
        new Response(200, [], '{"ok":true,"url":"https://report-one.deed.page/","slug":"report-one","token":"deed_tok_first"}'),
        new Response(200, [], '{"ok":true,"url":"https://report-two.deed.page/","slug":"report-two"}'),
        new Response(200, [], '{"ok":true,"deleted":"report-one"}'),
    ], $history);
    $tool = new DeedPagePublisher(client: $client);

    $tool->publishHtml('<p>1</p>', 'report-one');
    $tool->publishHtml('<p>2</p>', 'report-two');
    $tool->deleteSite('report-one');

    expect($tool->getToken())->toBe('deed_tok_first')
        ->and(json_decode((string) $history[0]['request']->getBody(), true)['slug'])->toBe('report-one')
        ->and($history[1]['request']->getHeaderLine('Authorization'))->toBe('Bearer deed_tok_first')
        ->and($history[2]['request']->getMethod())->toBe('DELETE')
        ->and((string) $history[2]['request']->getUri())->toBe('https://deed.page/v1/sites/report-one')
        ->and($history[2]['request']->getHeaderLine('Authorization'))->toBe('Bearer deed_tok_first');
});

it('uses a token from the environment and sends the configured expiry', function (): void {
    putenv('DEEDPAGE_TOKEN=deed_tok_from_env');
    $history = [];
    $client = deedPageClient([new Response(200, [], '{"ok":true,"url":"https://temp-page.deed.page/","slug":"temp-page"}')], $history);
    $tool = new DeedPagePublisher(client: $client, ttlSeconds: 600);

    $tool->publishHtml('<p>temp</p>');

    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer deed_tok_from_env')
        ->and($history[0]['request']->getHeaderLine('X-Ttl'))->toBe('600');
    putenv('DEEDPAGE_TOKEN');
});

it('rejects an invalid slug before sending anything', function (): void {
    $history = [];
    $tool = new DeedPagePublisher(client: deedPageClient([], $history));

    expect(fn (): string => $tool->publishHtml('<p>x</p>', 'Not Valid'))->toThrow(\Exception::class, 'Invalid slug');
    expect($history)->toBeEmpty()
        ->and($tool->wasSuccessful)->toBeFalse();
});

it('surfaces the deed.page error code and message', function (): void {
    $history = [];
    $client = deedPageClient([new Response(400, [], '{"code":"slug_reserved","message":"Slug \"hello\" is reserved."}')], $history);
    $tool = new DeedPagePublisher(client: $client);

    expect(fn (): string => $tool->publishHtml('<p>x</p>', 'hello'))
        ->toThrow(\Exception::class, 'deed.page publish failed (HTTP 400, slug_reserved): Slug "hello" is reserved.');
    expect($tool->wasSuccessful)->toBeFalse();
});

it('refuses to delete without a token', function (): void {
    $history = [];
    $tool = new DeedPagePublisher(client: deedPageClient([], $history));

    expect(fn () => $tool->deleteSite('some-page'))->toThrow(\Exception::class, 'owner token');
    expect($history)->toBeEmpty();
});

it('rejects a non-positive ttl', function (): void {
    expect(fn (): DeedPagePublisher => new DeedPagePublisher(client: new Client(), ttlSeconds: 0))
        ->toThrow(\Exception::class, 'ttlSeconds');
});

it('exposes publishHtml to the LLM with html required and slug optional', function (): void {
    $tool = new DeedPagePublisher(client: new Client());

    $functionInfo = FunctionBuilder::buildFunctionInfo($tool, 'publishHtml');

    expect($functionInfo->name)->toBe('publishHtml')
        ->and($functionInfo->parameters)->toHaveCount(2)
        ->and($functionInfo->parameters[0]->name)->toBe('html')
        ->and($functionInfo->parameters[1]->name)->toBe('slug')
        ->and($functionInfo->requiredParameters)->toHaveCount(1)
        ->and($functionInfo->description)->toContain('public URL');
});
